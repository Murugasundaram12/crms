<?php

namespace App\Http\Controllers;

use App\Models\Variation;
use App\Models\Project;
use App\Models\Employee;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VariationController extends Controller
{
    public function index(Request $request)
    {
        // Build the variation list query with search and filters.
        $variationQuery = Variation::with(['project', 'approvedBy']);
        $this->applySearchFilter($variationQuery, $request);
        $this->applyStatusFilter($variationQuery, $request);
        $this->applyTypeFilter($variationQuery, $request);
        $this->applyProjectFilter($variationQuery, $request);
        $this->applyDateFilter($variationQuery, $request);

        $totals = (clone $variationQuery)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'additional' THEN amount ELSE 0 END), 0) as total_additional")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'deduction' THEN amount ELSE 0 END), 0) as total_deduction")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' AND type = 'additional' THEN amount WHEN status = 'approved' AND type = 'deduction' THEN -amount ELSE 0 END), 0) as approved_net")
            ->first();

        // Load the variation list and supporting form data.
        $variations = $variationQuery->latest()->paginate(10)->withQueryString();
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('pages.variations.index', compact('variations', 'projects', 'employees', 'totals'));
    }

    public function store(Request $request)
    {
        // Validate the form before creating a variation.
        $validatedData = $this->validateVariationData($request);

        DB::transaction(function () use ($validatedData): void {
            $variation = Variation::create($validatedData);
            $this->applyVariationWallet($variation, 1);
        });

        return redirect()->route('variations.index', ['project_id' => $validatedData['project_id']])->with('success', 'Variation created successfully.');
    }

    public function show(Variation $variation)
    {
        return redirect()->route('variations.index', ['highlight' => $variation->id]);
    }

    public function edit(Variation $variation)
    {
        // Reuse the listing page and open the selected variation for editing.
        return redirect()->route('variations.index', ['edit' => $variation->id]);
    }

    public function update(Request $request, Variation $variation)
    {
        // Validate the form before updating the variation.
        $validatedData = $this->validateVariationData($request, $variation);

        DB::transaction(function () use ($variation, $validatedData): void {
            $this->applyVariationWallet($variation, -1);
            $variation->update($validatedData);
            $this->applyVariationWallet($variation->fresh(), 1);
        });

        return redirect()->route('variations.index', ['project_id' => $validatedData['project_id']])->with('success', 'Variation updated successfully.');
    }

    public function destroy(Variation $variation)
    {
        DB::transaction(function () use ($variation): void {
            $this->applyVariationWallet($variation, -1);
            $variation->delete();
        });

        return redirect()->route('variations.index')->with('success', 'Variation deleted successfully.');
    }

    private function applySearchFilter($variationQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $variationQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('description', 'like', "%{$searchTerm}%")
                ->orWhereHas('project', function ($projectQuery) use ($searchTerm) {
                    $projectQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    private function applyStatusFilter($variationQuery, Request $request): void
    {
        $status = $request->string('status')->toString();

        if ($status === '') {
            return;
        }

        $variationQuery->where('status', $status);
    }

    private function applyTypeFilter($variationQuery, Request $request): void
    {
        $type = $request->string('type')->toString();

        if ($type === '') {
            return;
        }

        $variationQuery->where('type', $type);
    }

    private function applyProjectFilter($variationQuery, Request $request): void
    {
        $projectId = $request->integer('project_id');

        if ($projectId === 0) {
            return;
        }

        $variationQuery->where('project_id', $projectId);
    }

    private function applyDateFilter($variationQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $variationQuery->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $variationQuery->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateVariationData(Request $request, ?Variation $variation = null): array
    {
        // Validate each field before saving the variation.
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::in(['additional', 'deduction'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'approved_by' => ['nullable', 'exists:employees,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);
    }

    private function applyVariationWallet(Variation $variation, int $direction): void
    {
        if ($variation->status !== 'approved' || ! $variation->approved_by || (float) $variation->amount <= 0) {
            return;
        }

        $userId = $this->userIdForEmployee((int) $variation->approved_by);
        if (! $userId) {
            return;
        }

        $signedAmount = (float) $variation->amount;
        if ($variation->type === 'deduction') {
            $signedAmount *= -1;
        }
        $signedAmount *= $direction;

        app(CrmBalanceService::class)->adjustUserWallet($userId, $signedAmount);
        $this->recordVariationWalletHistory($variation, $userId, abs($signedAmount), $signedAmount >= 0 ? 0 : 1);
    }

    private function userIdForEmployee(int $employeeId): ?int
    {
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return null;
        }

        $user = User::query()
            ->whereKey($employee->id)
            ->when($employee->email, fn ($query) => $query->orWhere('email', $employee->email))
            ->first(['id']);

        return $user?->id;
    }

    private function recordVariationWalletHistory(Variation $variation, int $userId, float $amount, int $transferType): void
    {
        if ($amount <= 0 || ! Schema::hasTable('wallet')) {
            return;
        }

        $project = $variation->project;
        if (! $project?->client_id) {
            return;
        }

        Wallet::query()->create([
            'user_id' => $userId,
            'client_id' => (int) $project->client_id,
            'project_id' => (int) $variation->project_id,
            'amount' => (int) round($amount),
            'payment_mode' => 1,
            'transfer_type' => $transferType,
            'stage_id' => null,
            'description' => 'Variation ' . ($variation->type === 'deduction' ? 'deduction' : 'addition') . ' - ' . $variation->description,
            'current_date' => $variation->date ? Carbon::parse($variation->date) : Carbon::now(),
            'active_status' => 1,
            'delete_status' => 0,
        ]);
    }
}
