<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Preorder;
use App\Models\ToolMaterial;
use App\Models\Unit;
use App\Models\Vendor;
use App\Services\PreorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobilePreorderEndpoints
{
    public function preorderOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $materials = ToolMaterial::query()
            ->where('active_status', true)
            ->orderBy('name')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ]);

        $vendors = Vendor::query()->orderBy('name')->get()->map(fn($v) => [
            'id' => $v->id,
            'name' => $v->name,
        ]);

        $units = Schema::hasTable('units')
            ? Unit::query()->active()->orderBy('name')->get()->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'code' => $u->code,
                'display_name' => $u->display_name,
            ])
            : [];

        $paymentMethods = \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get()->map(fn($pm) => [
            'id' => $pm->id,
            'name' => $pm->name,
        ]);

        $preorderStatuses = [];
        foreach (\App\Http\Controllers\PreorderController::STATUSES as $id => $name) {
            $preorderStatuses[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        $categories = Category::query()->orderBy('name')->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'materials' => $materials,
                'vendors' => $vendors,
                'units' => $units,
                'payment_methods' => $paymentMethods,
                'preorder_statuses' => $preorderStatuses,
                'delivery_statuses' => [
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'partially_delivered', 'name' => 'Partially Delivered'],
                    ['id' => 'delivered', 'name' => 'Delivered'],
                ],
                'payment_statuses' => [
                    ['id' => 'unpaid', 'name' => 'Unpaid'],
                    ['id' => 'partially_paid', 'name' => 'Partially Paid'],
                    ['id' => 'paid', 'name' => 'Paid'],
                ],
                'categories' => $categories,
            ],
        ]);
    }

    public function preorders(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'vendor_id' => ['nullable', 'integer'],
            'material_id' => ['nullable', 'integer'],
            'payment_status' => ['nullable', 'string'],
            'delivery_status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $validated['search'] ?? $validated['q'] ?? null;

        $query = Preorder::query()
            ->with(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vendor', fn($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['status'] ?? null, fn($q, string $status) => $q->where('status', $status))
            ->when($validated['vendor_id'] ?? null, fn($q, int $id) => $q->where('vendor_id', $id))
            ->when($validated['material_id'] ?? null, fn($q, int $id) => $q->where('tool_material_id', $id))
            ->when($validated['payment_status'] ?? null, fn($q, string $status) => $q->where('payment_status', $status))
            ->when($validated['delivery_status'] ?? null, fn($q, string $status) => $q->where('delivery_status', $status))
            ->when($validated['date_from'] ?? null, fn($q) => $q->whereDate('preorder_date', '>=', $request->date('date_from')->toDateString()))
            ->when($validated['date_to'] ?? null, fn($q) => $q->whereDate('preorder_date', '<=', $request->date('date_to')->toDateString()));

        // Safe sorting
        $allowedSortColumns = ['id', 'reference_no', 'preorder_date', 'quantity', 'rate', 'expected_rate', 'total_amount', 'advance_amount', 'remaining_amount', 'status', 'created_at', 'updated_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSortColumns, true) ? $request->input('sort_by') : 'id';
        $sortOrder = in_array(strtolower($request->input('sort_order', '')), ['asc', 'desc'], true) ? $request->input('sort_order') : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $preorders = $query->paginate($perPage);

        $data = $preorders->getCollection()->map(fn(Preorder $preorder) => $this->preorderPayload($preorder))->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $preorders->currentPage(),
                'per_page' => $preorders->perPage(),
                'total' => $preorders->total(),
                'last_page' => $preorders->lastPage(),
            ]
        ]);
    }

    public function storePreorder(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', Rule::exists('units', 'code')->where('active_status', true)],
            'expected_rate' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'preorder_date' => ['nullable', 'date'],
            'payment_method_id' => [
                Rule::requiredIf(fn() => (float) $request->input('advance_amount', 0) > 0),
                'nullable',
                'exists:payment_methods,id',
            ],
            'status' => ['nullable', Rule::in(array_keys(\App\Http\Controllers\PreorderController::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = ToolMaterial::query()->findOrFail((int) $validated['tool_material_id']);
        $validated['unit'] = $validated['unit'] ?? $material->unit ?? 'Nos';
        $validated['preorder_date'] = $validated['preorder_date'] ?? now()->toDateString();
        $validated['status'] = $validated['status'] ?? Preorder::STATUS_PENDING_APPROVAL;

        $qty = (float) $validated['quantity'];
        $rate = (float) $validated['expected_rate'];
        $gstPercent = (float) ($validated['gst_percent'] ?? 0);
        $advance = (float) ($validated['advance_amount'] ?? 0);

        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }
        if ($rate < 0) {
            throw ValidationException::withMessages(['expected_rate' => 'Expected rate cannot be negative.']);
        }
        if ($gstPercent < 0 || $gstPercent > 100) {
            throw ValidationException::withMessages(['gst_percent' => 'GST percent must be between 0 and 100.']);
        }

        $estimated = $qty * $rate;
        $gstAmount = $estimated * ($gstPercent / 100);
        $total = $estimated + $gstAmount;

        if ($advance > $total) {
            throw ValidationException::withMessages(['advance_amount' => 'Advance amount cannot exceed total amount (Rs. ' . number_format($total, 2) . ').']);
        }

        $validated['estimated_amount'] = $estimated;
        $validated['gst_amount'] = $gstAmount;
        $validated['total_amount'] = $total;
        $validated['advance_amount'] = $advance;
        $validated['remaining_amount'] = max(0, $total - $advance);

        $preorder = app(PreorderService::class)->createPreorder($validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Preorder created successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator'])),
        ], 201);
    }

    public function showPreorder(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-list')) {
            return $forbidden;
        }

        return response()->json([
            'success' => true,
            'data' => $this->preorderPayload($preorder->load(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ]);
    }

    public function updatePreorder(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', Rule::exists('units', 'code')],
            'expected_rate' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'required_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'preorder_date' => ['nullable', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'status' => ['nullable', Rule::in(array_keys(\App\Http\Controllers\PreorderController::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = ToolMaterial::query()->findOrFail((int) $validated['tool_material_id']);
        $validated['unit'] = $validated['unit'] ?? $material->unit ?? 'Nos';
        $validated['preorder_date'] = $validated['preorder_date'] ?? now()->toDateString();
        $validated['status'] = $validated['status'] ?? $preorder->status;

        $qty = (float) $validated['quantity'];
        $rate = (float) $validated['expected_rate'];
        $gstPercent = (float) ($validated['gst_percent'] ?? 0);

        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }
        if ($rate < 0) {
            throw ValidationException::withMessages(['expected_rate' => 'Expected rate cannot be negative.']);
        }
        if ($gstPercent < 0 || $gstPercent > 100) {
            throw ValidationException::withMessages(['gst_percent' => 'GST percent must be between 0 and 100.']);
        }

        $estimated = $qty * $rate;
        $gstAmount = $estimated * ($gstPercent / 100);
        $total = $estimated + $gstAmount;

        $totalPaid = $preorder->totalAdvancePaid();
        if ($totalPaid > $total) {
            throw ValidationException::withMessages(['quantity' => 'Total calculated amount cannot be less than total advance paid (Rs. ' . number_format($totalPaid, 2) . ').']);
        }

        $preorder->update([
            'tool_material_id' => $validated['tool_material_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'quantity' => $qty,
            'unit' => $validated['unit'],
            'rate' => $rate,
            'expected_rate' => $rate,
            'estimated_amount' => $estimated,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'total_amount' => $total,
            'required_date' => $validated['required_date'] ?? null,
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'preorder_date' => $validated['preorder_date'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        $preorder->recalculateStatuses();

        app(PreorderService::class)->logAudit($preorder->id, 'edited', 'Preorder updated via API', $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Preorder updated successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator'])),
        ]);
    }

    public function approvePreorder(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $preorder = app(PreorderService::class)->approvePreorder($preorder, $request->user()->id, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Preorder approved successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ]);
    }

    public function rejectPreorder(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $preorder = app(PreorderService::class)->rejectPreorder($preorder, $request->user()->id, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Preorder rejected successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ]);
    }

    public function changePreorderStatus(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-edit')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(\App\Http\Controllers\PreorderController::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $preorder = app(PreorderService::class)->changeStatus($preorder, $validated['status'], $request->user()->id, $validated['notes'] ?? null);
        $preorder->recalculateStatuses();

        return response()->json([
            'success' => true,
            'message' => 'Preorder status updated successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ]);
    }

    public function addPreorderAdvance(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'deduct_wallet' => ['nullable', 'boolean'],
        ]);

        $remainingPayable = max(0, (float) $preorder->total_amount - $preorder->totalAdvancePaid());
        if ((float) $validated['amount'] > $remainingPayable + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Advance amount cannot exceed remaining payable amount (Rs. ' . number_format($remainingPayable, 2) . ').',
            ]);
        }

        $validated['payment_date'] = $validated['payment_date'] ?? now()->toDateString();
        app(PreorderService::class)->addAdvancePayment($preorder, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Advance payment recorded successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ], 201);
    }

    public function recordPreorderDelivery(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        $remaining = $preorder->remainingQuantity();
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:' . $remaining],
            'rate' => ['nullable', 'numeric', 'min:0.01'],
            'purchase_amount' => ['nullable', 'numeric', 'min:0.01'],
            'purchase_paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'delivery_date' => ['nullable', 'date'],
            'challan_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['vendor_id'] = $validated['vendor_id'] ?? $preorder->vendor_id;
        $validated['rate'] = $validated['rate'] ?? $preorder->rate ?? $preorder->expected_rate;
        $validated['purchase_amount'] = $validated['purchase_amount'] ?? round((float) $validated['quantity'] * (float) $validated['rate'], 2);
        $validated['payment_method_id'] = $validated['payment_method_id'] ?? $preorder->payment_method_id;
        $validated['delivery_date'] = $validated['delivery_date'] ?? now()->toDateString();
        app(PreorderService::class)->recordDelivery($preorder, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Delivery recorded successfully and stock updated.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ], 201);
    }

    public function convertPreorderToPurchase(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-create')) {
            return $forbidden;
        }

        if (! $preorder->canBeConvertedToPurchase()) {
            return response()->json([
                'success' => false,
                'message' => 'This preorder cannot be converted. It must be approved and have remaining quantity.',
            ], 422);
        }

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'max:' . $preorder->remainingQuantity()],
            'rate' => ['nullable', 'numeric', 'min:0.01'],
            'purchase_amount' => ['nullable', 'numeric', 'min:0.01'],
            'purchase_paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'transferred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quantity = (float) ($validated['quantity'] ?? $preorder->remainingQuantity());
        $rate = (float) ($validated['rate'] ?? $preorder->rate);
        $validated['vendor_id'] = $validated['vendor_id'] ?? $preorder->vendor_id;
        $validated['quantity'] = $quantity;
        $validated['rate'] = $rate;
        $validated['purchase_amount'] = $validated['purchase_amount'] ?? round($quantity * $rate, 2);
        $validated['payment_method_id'] = $validated['payment_method_id'] ?? $preorder->payment_method_id;
        $validated['transferred_at'] = $validated['transferred_at'] ?? now()->toDateString();

        if (empty($validated['vendor_id'])) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor is required to convert preorder to purchase.']);
        }

        app(PreorderService::class)->convertToPurchase($preorder, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Preorder converted to purchase successfully.',
            'data' => $this->preorderPayload($preorder->fresh(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'deliveries', 'advances'])),
        ], 201);
    }

    public function deletePreorder(Request $request, Preorder $preorder)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tools-materials-delete')) {
            return $forbidden;
        }

        if ($preorder->assignments()->exists() || $preorder->deliveries()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete preorder linked to purchases or deliveries.',
            ], 409);
        }

        $preorder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Preorder deleted successfully.',
        ]);
    }

    private function preorderPayload(Preorder $preorder): array
    {
        return [
            'id' => $preorder->id,
            'reference_no' => $preorder->reference_no,
            'tool_material_id' => (int) $preorder->tool_material_id,
            'tool_material' => $preorder->toolMaterial ? [
                'id' => $preorder->toolMaterial->id,
                'name' => $preorder->toolMaterial->name,
                'sku' => $preorder->toolMaterial->sku,
            ] : null,
            'vendor_id' => $preorder->vendor_id ? (int) $preorder->vendor_id : null,
            'vendor' => $preorder->vendor ? [
                'id' => $preorder->vendor->id,
                'name' => $preorder->vendor->name,
            ] : null,
            'quantity' => (float) $preorder->quantity,
            'unit' => $preorder->unit,
            'rate' => (float) $preorder->rate,
            'expected_rate' => (float) $preorder->expected_rate,
            'estimated_amount' => (float) $preorder->estimated_amount,
            'gst_percent' => (float) $preorder->gst_percent,
            'gst_amount' => (float) $preorder->gst_amount,
            'total_amount' => (float) $preorder->total_amount,
            'advance_amount' => (float) $preorder->advance_amount,
            'remaining_amount' => (float) $preorder->remaining_amount,
            'expected_delivery_date' => $preorder->expected_delivery_date?->toDateString(),
            'required_date' => $preorder->required_date?->toDateString(),
            'preorder_date' => $preorder->preorder_date?->toDateString(),
            'payment_method_id' => $preorder->payment_method_id ? (int) $preorder->payment_method_id : null,
            'payment_method' => $preorder->paymentMethod ? [
                'id' => $preorder->paymentMethod->id,
                'name' => $preorder->paymentMethod->name,
            ] : null,
            'status' => $preorder->status,
            'delivery_status' => $preorder->delivery_status,
            'payment_status' => $preorder->payment_status,
            'notes' => $preorder->notes,
            'created_at' => $preorder->created_at?->toISOString(),
            'updated_at' => $preorder->updated_at?->toISOString(),
        ];
    }
}
