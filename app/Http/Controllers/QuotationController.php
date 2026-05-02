<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSchedule;
use App\Models\QuotationTerm;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    private const DEFAULT_STATUS = 'draft';
    private ?array $quotationColumns = null;

    /**
     * Display a listing of the resource.
     */
    public function list(Request $request)
    {
        if ($request->ajax() && $request->filled('client_details')) {
            $client = Client::with(['projects' => fn($query) => $query->orderBy('name')])
                ->findOrFail($request->integer('client_details'));

            return $this->buildClientDetailsResponse($client);
        }

        $query = Quotation::with(['client', 'project'])
            ->latest();

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('notes', 'like', "%{$searchTerm}%")
                    ->orWhere('quotation_title', 'like', "%{$searchTerm}%")
                    ->orWhereHas('client', function ($clientQ) use ($searchTerm) {
                        $clientQ->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->filled('status') && $this->quotationColumnExists('status')) {
            $query->where('status', $request->status);
        }

        $quotations = $query->paginate(10);

        return view('pages.quotations.index_new', compact('quotations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->ajax() && $request->filled('client_details')) {
            $client = Client::with(['projects' => fn($query) => $query->orderBy('name')])
                ->findOrFail($request->integer('client_details'));

            return $this->buildClientDetailsResponse($client);
        }

        $clients = Client::orderBy('name')
            ->get(['id', 'name', 'company_name', 'email', 'phone', 'address', 'city', 'state', 'country']);

        return view('pages.quotations.create', compact('clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuotationRequest $request)
    {
        $validated = $request->validated();
        $quotationData = $this->buildQuotationPayload($validated);

        // Auto-generate unique quotation number
        $nextId = Quotation::max('id') ?? 0; // safe increment
        $quotationData['quotation_number'] = sprintf('QTN-%04d', $nextId + 1);

        DB::transaction(function () use ($quotationData, $validated) {
            $quotation = Quotation::create($quotationData);
            $this->syncItems($quotation, $validated['items']);
            $this->syncDefaultTerms($quotation);
        });

        return redirect()->route('quotations.list')->with('success', 'Quotation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'schedules', 'terms']);

        return view('pages.quotations.show', compact('quotation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Quotation $quotation)
    {
        if ($request->query('download') === 'pdf') {
            return $this->downloadPdf($quotation);
        }

        $clients = Client::orderBy('name')
            ->get(['id', 'name', 'company_name', 'email', 'phone', 'address', 'city', 'state', 'country']);
        $quotation->load(['items', 'schedules', 'terms', 'project']);
        $groupedItems = $this->groupQuotationItems($quotation);

        return view('pages.quotations.edit', compact('quotation', 'clients', 'groupedItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $validated = $request->validated();
        $quotationData = $this->buildQuotationPayload($validated);

        DB::transaction(function () use ($quotationData, $validated, $quotation) {
            $quotation->update($quotationData);

            QuotationItem::where('quotation_id', $quotation->id)->delete();
            QuotationSchedule::where('quotation_id', $quotation->id)->delete();
            QuotationTerm::where('quotation_id', $quotation->id)->delete();

            $this->syncItems($quotation, $validated['items']);
            $this->syncDefaultTerms($quotation);
        });

        return redirect()->route('quotations.list')->with('success', 'Quotation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        DB::transaction(function () use ($quotation) {
            $quotation->items()->delete();
            $quotation->schedules()->delete();
            $quotation->terms()->delete();
            $quotation->delete();
        });

        return redirect()->route('quotations.list')->with('success', 'Quotation deleted successfully.');
    }

    /**
     * Download PDF
     */
    public function downloadPdf(Quotation $quotation)
    {
        $quotation->load(['client', 'project', 'items', 'schedules', 'terms']);
        $groupedItems = $this->groupQuotationItems($quotation);
        $bankDetails = config('quotation.bank_details', []);
        $pdfTerms = $this->resolvePdfTerms($quotation);

        $pdf = Pdf::loadView('pdf.quotation', compact('quotation', 'groupedItems', 'bankDetails', 'pdfTerms'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Quotation-' . $quotation->id . '.pdf');
    }

    public function clientDetails(Client $client): JsonResponse
    {
        $client->load(['projects' => function ($query) {
            $query->orderBy('name');
        }]);

        return $this->buildClientDetailsResponse($client);
    }

    private function buildClientDetailsResponse(Client $client): JsonResponse
    {
        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'state' => $client->state,
                'country' => $client->country,
                'full_address' => collect([$client->address, $client->city, $client->state, $client->country])
                    ->filter()
                    ->implode(', '),
            ],
            'projects' => $client->projects->map(fn(Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'project_code' => $project->project_code,
                'location' => $project->location,
            ])->values(),
        ]);
    }

    private function buildQuotationPayload(array $validated): array
    {
        $quotationData = $validated;
        $items = $quotationData['items'];
        unset($quotationData['items']);

        $totalAmount = collect($items)
            ->flatMap(fn(array $group) => $group['rows'])
            ->sum(fn(array $row) => round(((float) ($row['quantity'] ?? 0)) * ((float) ($row['price'] ?? 0)), 2));

        $quotationData['amount'] = $totalAmount;
        if ($this->quotationColumnExists('sub_total')) {
            $quotationData['sub_total'] = $totalAmount;
        }
        if ($this->quotationColumnExists('total_amount')) {
            $quotationData['total_amount'] = $totalAmount;
        }
        if ($this->quotationColumnExists('status')) {
            $quotationData['status'] = $quotationData['status'] ?? self::DEFAULT_STATUS;
        }

        $firstMainTitle = $items[0]['main_title'] ?? null;
        $firstSubTitle = $items[0]['rows'][0]['description'] ?? null;

        $quotationData['main_title'] = $quotationData['main_title'] ?? $firstMainTitle;
        $quotationData['sub_title'] = $quotationData['sub_title'] ?? $firstSubTitle;

        return array_intersect_key($quotationData, array_flip($this->getQuotationColumns()));
    }

    private function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $mainIndex => $group) {
            $mainTitle = $group['main_title'] ?? 'Untitled Section';

            foreach (($group['rows'] ?? []) as $rowIndex => $row) {
                $quantity = (float) ($row['quantity'] ?? 0);
                $price = (float) ($row['price'] ?? 0);
                $amount = round($quantity * $price, 2);

                $quotation->items()->create([
                    'main_title' => $mainTitle,
                    'main_title_order' => $mainIndex,
                    'item_order' => $rowIndex,
                    'description' => $row['description'],
                    'nos' => $row['nos'] ?? null,
                    'length' => $row['length'] ?? null,
                    'breadth' => $row['breadth'] ?? null,
                    'depth' => $row['depth'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $row['unit'] ?? null,
                    'price' => $price,
                    'rate' => $price,
                    'amount' => $amount,
                ]);
            }
        }
    }

    private function syncDefaultTerms(Quotation $quotation): void
    {
        foreach (config('quotation.default_terms', []) as $term) {
            $quotation->terms()->create([
                'term_text' => $term,
            ]);
        }
    }

    private function groupQuotationItems(Quotation $quotation): array
    {
        return $quotation->items
            ->sortBy([
                ['main_title_order', 'asc'],
                ['item_order', 'asc'],
                ['id', 'asc'],
            ])
            ->groupBy(fn(QuotationItem $item) => $item->main_title ?: 'Untitled Section')
            ->map(function ($rows, $mainTitle) {
                return [
                    'main_title' => $mainTitle,
                    'rows' => $rows->map(fn(QuotationItem $item) => [
                        'description' => $item->description,
                        'nos' => $item->nos,
                        'length' => $item->length,
                        'breadth' => $item->breadth,
                        'depth' => $item->depth,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'price' => $item->price ?? $item->rate,
                        'amount' => $item->amount,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolvePdfTerms(Quotation $quotation): array
    {
        $configTerms = collect(config('quotation.default_terms', []))
            ->map(fn($term) => trim((string) $term))
            ->filter()
            ->values()
            ->all();

        if ($configTerms !== []) {
            return $configTerms;
        }

        return $quotation->terms
            ->pluck('term_text')
            ->map(fn($term) => trim((string) $term))
            ->filter()
            ->values()
            ->all();
    }

    private function getQuotationColumns(): array
    {
        return $this->quotationColumns ??= Schema::getColumnListing('quotations');
    }

    private function quotationColumnExists(string $column): bool
    {
        return in_array($column, $this->getQuotationColumns(), true);
    }
}
