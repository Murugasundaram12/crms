<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Preorder;
use App\Models\PreorderDocument;
use App\Models\ToolMaterial;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PreorderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PreorderController extends Controller
{
    public const STATUSES = [
        Preorder::STATUS_DRAFT => 'Draft',
        Preorder::STATUS_PENDING_APPROVAL => 'Pending Approval',
        Preorder::STATUS_APPROVED => 'Approved',
        Preorder::STATUS_ORDERED => 'Ordered',
        Preorder::STATUS_PARTIALLY_DELIVERED => 'Partially Delivered',
        Preorder::STATUS_DELIVERED => 'Delivered',
        Preorder::STATUS_CLOSED => 'Closed',
        Preorder::STATUS_REJECTED => 'Rejected',
        Preorder::STATUS_CANCELLED => 'Cancelled',
        Preorder::STATUS_HOLD => 'On Hold',
    ];

    public function __construct(
        protected PreorderService $preorderService
    ) {}

    public function index(Request $request): View
    {
        $query = Preorder::query()->with(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('tool_material_id')) {
            $query->where('tool_material_id', $request->integer('tool_material_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->integer('created_by'));
        }

        if ($request->filled('approved_by')) {
            $query->where('approved_by', $request->integer('approved_by'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->string('delivery_status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('preorder_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('preorder_date', '<=', $request->date('date_to')->toDateString());
        }

        $preorders = (clone $query)
            ->latest('preorder_date')
            ->latest()
            ->paginate((int) $request->input('paginate', 10))
            ->withQueryString();

        // Dashboard KPI Metrics
        $allPreorders = Preorder::query()->get();
        $metrics = [
            'total' => $allPreorders->count(),
            'pending_approval' => $allPreorders->whereIn('status', [Preorder::STATUS_DRAFT, Preorder::STATUS_PENDING_APPROVAL])->count(),
            'approved' => $allPreorders->where('status', Preorder::STATUS_APPROVED)->count(),
            'ordered' => $allPreorders->where('status', Preorder::STATUS_ORDERED)->count(),
            'partially_delivered' => $allPreorders->where('status', Preorder::STATUS_PARTIALLY_DELIVERED)->count(),
            'delivered' => $allPreorders->where('status', Preorder::STATUS_DELIVERED)->count(),
            'cancelled' => $allPreorders->where('status', Preorder::STATUS_CANCELLED)->count(),
            'pending_deliveries' => $allPreorders->whereIn('status', [Preorder::STATUS_APPROVED, Preorder::STATUS_ORDERED, Preorder::STATUS_PARTIALLY_DELIVERED])->count(),
            'low_stock_materials' => ToolMaterial::query()->where('active_status', true)->get()->filter(fn($tm) => $tm->is_low_stock)->count(),
        ];

        return view('pages.preorders.index', [
            'preorders' => $preorders,
            'metrics' => $metrics,
            'statuses' => self::STATUSES,
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'toolsMaterials' => ToolMaterial::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Preorder $preorder): View
    {
        $preorder->load([
            'toolMaterial',
            'vendor',
            'paymentMethod',
            'creator',
            'updater',
            'approver',
            'statusHistories.changer',
            'advances.paymentMethod',
            'advances.payer',
            'deliveries.receiver',
            'deliveries.assignment',
            'documents.uploader',
            'auditLogs.performer',
        ]);

        return view('pages.preorders.show', [
            'preorder' => $preorder,
            'statuses' => self::STATUSES,
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.preorders.create', [
            'toolsMaterials' => ToolMaterial::query()->where('active_status', true)->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'expected_rate' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'preorder_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        $this->preorderService->createPreorder($validated, Auth::id());

        return redirect()->route('preorders.index')->with('success', 'Preorder created successfully.');
    }

    public function edit(Preorder $preorder): View
    {
        return view('pages.preorders.edit', [
            'preorder' => $preorder,
            'toolsMaterials' => ToolMaterial::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Preorder $preorder): RedirectResponse
    {
        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'expected_rate' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'required_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'preorder_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quantity = (float) $validated['quantity'];
        $rate = (float) $validated['expected_rate'];
        $estimated = $quantity * $rate;
        $gstPercent = (float) ($validated['gst_percent'] ?? 0);
        $gstAmount = $estimated * ($gstPercent / 100);
        $totalAmount = $estimated + $gstAmount;

        $preorder->update([
            'tool_material_id' => $validated['tool_material_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'quantity' => $quantity,
            'unit' => $validated['unit'],
            'rate' => $rate,
            'expected_rate' => $rate,
            'estimated_amount' => $estimated,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
            'required_date' => $validated['required_date'] ?? null,
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'preorder_date' => $validated['preorder_date'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $preorder->recalculateStatuses();

        $this->preorderService->logAudit($preorder->id, 'edited', 'Preorder updated', Auth::id());

        return redirect()->route('preorders.show', $preorder->id)->with('success', 'Preorder updated successfully.');
    }

    public function destroy(Preorder $preorder): RedirectResponse
    {
        if ($preorder->assignments()->exists() || $preorder->deliveries()->exists()) {
            return redirect()->route('preorders.index')
                ->with('error', 'Cannot delete preorder linked to purchases or deliveries.');
        }

        $preorder->delete();

        return redirect()->route('preorders.index')->with('success', 'Preorder deleted successfully.');
    }

    public function approve(Request $request, Preorder $preorder): RedirectResponse
    {
        $notes = $request->input('notes');
        $this->preorderService->approvePreorder($preorder, Auth::id(), $notes);

        return redirect()->back()->with('success', 'Preorder approved successfully.');
    }

    public function reject(Request $request, Preorder $preorder): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->preorderService->rejectPreorder($preorder, Auth::id(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Preorder rejected.');
    }

    public function changeStatus(Request $request, Preorder $preorder): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->preorderService->changeStatus($preorder, $request->string('status')->toString(), Auth::id(), $request->input('notes'));

        return redirect()->back()->with('success', 'Preorder status updated to ' . ucfirst($request->string('status')->toString()) . '.');
    }

    public function addAdvance(Request $request, Preorder $preorder): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'deduct_wallet' => ['nullable', 'boolean'],
        ]);

        $this->preorderService->addAdvancePayment($preorder, $validated, Auth::id());

        return redirect()->back()->with('success', 'Advance payment recorded successfully.');
    }

    public function recordDelivery(Request $request, Preorder $preorder): RedirectResponse
    {
        $remaining = $preorder->remainingQuantity();

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:' . $remaining],
            'delivery_date' => ['required', 'date'],
            'challan_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.max' => 'Delivered quantity cannot exceed remaining ordered quantity (' . $remaining . ').',
        ]);

        $this->preorderService->recordDelivery($preorder, $validated, Auth::id());

        return redirect()->back()->with('success', 'Delivery recorded successfully and stock updated.');
    }

    public function uploadDocument(Request $request, Preorder $preorder): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', Rule::in(['quotation', 'vendor_pdf', 'invoice', 'delivery_challan', 'purchase_order', 'other'])],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $this->preorderService->uploadDocument($preorder, $request->file('file'), $validated['document_type'], $validated['title'], Auth::id());

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(PreorderDocument $document): RedirectResponse
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $preorderId = $document->preorder_id;
        $document->delete();

        return redirect()->back()->with('success', 'Document deleted.');
    }

    public function showConvertToPurchase(Preorder $preorder): View
    {
        if (! $preorder->canBeConvertedToPurchase()) {
            throw ValidationException::withMessages(['status' => 'This preorder cannot be converted to purchase. It must be approved and have remaining undelivered quantity.']);
        }

        $vendors = Vendor::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('pages.preorders.convert', [
            'preorder' => $preorder,
            'vendors' => $vendors,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function convertToPurchase(Request $request, Preorder $preorder): RedirectResponse
    {
        if (! $preorder->canBeConvertedToPurchase()) {
            return redirect()->route('preorders.index')
                ->with('error', 'This preorder cannot be converted to purchase. Check approval status and remaining quantity.');
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:' . $preorder->remainingQuantity()],
            'rate' => ['required', 'numeric', 'min:0'],
            'purchase_amount' => ['required', 'numeric', 'min:0'],
            'purchase_paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'transferred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->preorderService->convertToPurchase($preorder, $validated, Auth::id());

        return redirect()->route('preorders.show', $preorder->id)->with('success', 'Preorder successfully converted into purchase and stock updated.');
    }
}
