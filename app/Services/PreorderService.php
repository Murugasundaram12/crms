<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Preorder;
use App\Models\PreorderAdvance;
use App\Models\PreorderAuditLog;
use App\Models\PreorderDelivery;
use App\Models\PreorderDocument;
use App\Models\PreorderStatusHistory;
use App\Models\ToolMaterialAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PreorderService
{
    public function __construct(
        protected CrmBalanceService $balanceService
    ) {}

    public function createPreorder(array $data, int $userId): Preorder
    {
        return DB::transaction(function () use ($data, $userId) {
            $quantity = (float) $data['quantity'];
            $rate = (float) ($data['expected_rate'] ?? $data['rate'] ?? 0);
            $estimatedAmount = (float) ($data['estimated_amount'] ?? ($quantity * $rate));
            $gstPercent = (float) ($data['gst_percent'] ?? 0);
            $gstAmount = (float) ($data['gst_amount'] ?? ($estimatedAmount * ($gstPercent / 100)));
            $totalAmount = (float) ($data['total_amount'] ?? ($estimatedAmount + $gstAmount));

            $initialAdvance = (float) ($data['advance_amount'] ?? 0);
            $remainingAmount = max(0, $totalAmount - $initialAdvance);

            $status = $data['status'] ?? Preorder::STATUS_PENDING_APPROVAL;
            $referenceNo = $this->nextReferenceNumber();

            $preorder = Preorder::create([
                'reference_no' => $referenceNo,
                'tool_material_id' => $data['tool_material_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'quantity' => $quantity,
                'unit' => $data['unit'],
                'rate' => $rate,
                'expected_rate' => $rate,
                'estimated_amount' => $estimatedAmount,
                'gst_percent' => $gstPercent,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'advance_amount' => $initialAdvance,
                'remaining_amount' => $remainingAmount,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'required_date' => $data['required_date'] ?? null,
                'preorder_date' => $data['preorder_date'] ?? now()->toDateString(),
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'status' => $status,
                'delivery_status' => 'pending',
                'payment_status' => $initialAdvance > 0 ? ($initialAdvance >= $totalAmount ? 'paid' : 'partially_paid') : 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Status History & Audit Trail
            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => null,
                'to_status' => $status,
                'changed_by' => $userId,
                'notes' => 'Preorder created',
            ]);

            $this->logAudit($preorder->id, 'created', 'Preorder ' . $referenceNo . ' created in status: ' . $status, $userId);

            // Handle optional initial advance payment with wallet deduction
            if ($initialAdvance > 0 && ! empty($data['payment_method_id'])) {
                $this->addAdvancePayment($preorder, [
                    'amount' => $initialAdvance,
                    'payment_method_id' => $data['payment_method_id'],
                    'payment_date' => $data['preorder_date'] ?? now()->toDateString(),
                    'notes' => 'Initial advance payment on preorder creation',
                    'deduct_wallet' => true,
                ], $userId);
            }

            // Handle optional attachment upload
            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $this->uploadDocument($preorder, $data['attachment'], 'quotation', 'Initial Quotation / Attachment', $userId);
            }

            return $preorder;
        });
    }

    public function approvePreorder(Preorder $preorder, int $approverId, ?string $notes = null): Preorder
    {
        return DB::transaction(function () use ($preorder, $approverId, $notes) {
            $fromStatus = $preorder->status;
            $preorder->status = Preorder::STATUS_APPROVED;
            $preorder->approved_by = $approverId;
            $preorder->approval_date = now();
            $preorder->updated_by = $approverId;
            $preorder->save();

            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => $fromStatus,
                'to_status' => Preorder::STATUS_APPROVED,
                'changed_by' => $approverId,
                'notes' => $notes ?? 'Preorder approved by manager',
            ]);

            $this->logAudit($preorder->id, 'approved', 'Preorder approved by Manager', $approverId, ['notes' => $notes]);

            return $preorder;
        });
    }

    public function rejectPreorder(Preorder $preorder, int $userId, string $reason): Preorder
    {
        return DB::transaction(function () use ($preorder, $userId, $reason) {
            $fromStatus = $preorder->status;
            $preorder->status = Preorder::STATUS_REJECTED;
            $preorder->rejection_reason = $reason;
            $preorder->updated_by = $userId;
            $preorder->save();

            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => $fromStatus,
                'to_status' => Preorder::STATUS_REJECTED,
                'changed_by' => $userId,
                'notes' => 'Rejection Reason: ' . $reason,
            ]);

            $this->logAudit($preorder->id, 'rejected', 'Preorder rejected', $userId, ['reason' => $reason]);

            return $preorder;
        });
    }

    public function changeStatus(Preorder $preorder, string $newStatus, int $userId, ?string $notes = null): Preorder
    {
        return DB::transaction(function () use ($preorder, $newStatus, $userId, $notes) {
            $fromStatus = $preorder->status;
            $preorder->status = $newStatus;
            $preorder->updated_by = $userId;
            $preorder->save();

            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'changed_by' => $userId,
                'notes' => $notes,
            ]);

            $this->logAudit($preorder->id, 'status_changed', 'Status changed to ' . $newStatus, $userId, ['from' => $fromStatus, 'to' => $newStatus, 'notes' => $notes]);

            return $preorder;
        });
    }

    public function addAdvancePayment(Preorder $preorder, array $data, int $userId): PreorderAdvance
    {
        return DB::transaction(function () use ($preorder, $data, $userId) {
            $amount = (float) $data['amount'];
            $paymentMethodId = (int) $data['payment_method_id'];
            $paymentDate = $data['payment_date'] ?? now()->toDateString();
            $referenceNumber = $data['reference_number'] ?? ('ADV-' . strtoupper(str()->random(6)));
            $notes = $data['notes'] ?? null;
            $deductWallet = isset($data['deduct_wallet']) ? (bool) $data['deduct_wallet'] : true;

            $attachmentPath = null;
            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $attachmentPath = $data['attachment']->store('preorder-advances', 'public');
            }

            // Deduct user wallet inside atomic transaction if enabled
            if ($deductWallet && $amount > 0) {
                $pm = PaymentMethod::find($paymentMethodId);
                $pmName = $pm?->name ?? 'Payment Method';
                $description = 'Advance Payment for Preorder ' . $preorder->reference_no . ' via ' . $pmName;

                $this->balanceService->debitUserWallet($userId, $amount, $description, 'preorder_advance', (int) $preorder->id);
            }

            $advance = PreorderAdvance::create([
                'preorder_id' => $preorder->id,
                'amount' => $amount,
                'payment_method_id' => $paymentMethodId,
                'payment_date' => $paymentDate,
                'reference_number' => $referenceNumber,
                'paid_by' => $userId,
                'notes' => $notes,
                'attachment_path' => $attachmentPath,
                'wallet_debited' => $deductWallet,
            ]);

            $preorder->recalculateStatuses();

            $this->logAudit($preorder->id, 'advance_paid', 'Advance payment of Rs. ' . number_format($amount, 2) . ' recorded.', $userId, [
                'advance_id' => $advance->id,
                'amount' => $amount,
                'payment_method_id' => $paymentMethodId,
            ]);

            return $advance;
        });
    }

    public function recordDelivery(Preorder $preorder, array $data, int $userId): PreorderDelivery
    {
        return DB::transaction(function () use ($preorder, $data, $userId) {
            $quantity = (float) $data['quantity'];
            $deliveryDate = $data['delivery_date'] ?? now()->toDateString();
            $challanNo = $data['challan_no'] ?? null;
            $notes = $data['notes'] ?? null;

            $nextDeliveryCount = $preorder->deliveries()->count() + 1;
            $deliveryNumber = 'DEL-' . $preorder->reference_no . '-' . $nextDeliveryCount;

            // Create Purchase Assignment which automatically increases stock
            $nextRefId = ((int) ToolMaterialAssignment::query()->max('id')) + 1;
            $refNo = 'PUR-DEL-' . now()->format('ymd') . '-' . str_pad((string) $nextRefId, 4, '0', STR_PAD_LEFT);

            $assignment = ToolMaterialAssignment::create([
                'preorder_id' => $preorder->id,
                'tool_material_id' => $preorder->tool_material_id,
                'vendor_id' => $preorder->vendor_id,
                'reference_no' => $refNo,
                'status' => 'transferred', // Stock effective
                'handled_by' => $userId,
                'transfer_type' => 'purchase',
                'transaction_type' => 'purchase',
                'source_type' => 'vendor',
                'destination_type' => 'office',
                'quantity' => $quantity,
                'unit' => $preorder->unit,
                'rate' => $preorder->rate,
                'amount' => $quantity * (float) $preorder->rate,
                'advance_amount' => 0,
                'payment_method_id' => $preorder->payment_method_id,
                'transferred_at' => $deliveryDate,
                'notes' => 'Delivery Receipt ' . $deliveryNumber . ' for Preorder ' . $preorder->reference_no . '. ' . ($notes ?? ''),
            ]);

            $delivery = PreorderDelivery::create([
                'preorder_id' => $preorder->id,
                'delivery_number' => $deliveryNumber,
                'quantity' => $quantity,
                'delivery_date' => $deliveryDate,
                'challan_no' => $challanNo,
                'received_by' => $userId,
                'notes' => $notes,
                'assignment_id' => $assignment->id,
            ]);

            // Update status based on total delivered quantity
            $totalDelivered = $preorder->totalDeliveredQuantity();
            $orderedQty = (float) $preorder->quantity;

            if ($totalDelivered >= $orderedQty && $orderedQty > 0) {
                $preorder->status = Preorder::STATUS_DELIVERED;
            } else {
                $preorder->status = Preorder::STATUS_PARTIALLY_DELIVERED;
            }
            $preorder->updated_by = $userId;
            $preorder->save();

            $preorder->recalculateStatuses();

            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => $preorder->getOriginal('status'),
                'to_status' => $preorder->status,
                'changed_by' => $userId,
                'notes' => 'Delivery recorded: Qty ' . $quantity . ' (Delivery #' . $deliveryNumber . ')',
            ]);

            $this->logAudit($preorder->id, 'delivery_received', 'Delivery of ' . $quantity . ' ' . $preorder->unit . ' received.', $userId, [
                'delivery_id' => $delivery->id,
                'quantity' => $quantity,
                'delivery_number' => $deliveryNumber,
            ]);

            return $delivery;
        });
    }

    public function convertToPurchase(Preorder $preorder, array $data, int $userId): ToolMaterialAssignment
    {
        return DB::transaction(function () use ($preorder, $data, $userId) {
            $vendorId = $data['vendor_id'] ?? $preorder->vendor_id;
            $quantity = (float) ($data['quantity'] ?? $preorder->remainingQuantity());
            $rate = (float) ($data['rate'] ?? $preorder->rate);
            $totalAmount = (float) ($data['purchase_amount'] ?? ($quantity * $rate));
            $paidNow = (float) ($data['purchase_paid_amount'] ?? 0);
            $paymentMethodId = $data['payment_method_id'] ?? $preorder->payment_method_id;
            $transferredAt = $data['transferred_at'] ?? now()->toDateString();
            $notes = $data['notes'] ?? null;

            $totalAdvance = $preorder->totalAdvancePaid();
            $balanceDue = max(0, $totalAmount - $totalAdvance - $paidNow);

            $nextRefId = ((int) ToolMaterialAssignment::query()->max('id')) + 1;
            $referenceNo = 'PUR-' . now()->format('ymd') . '-' . str_pad((string) $nextRefId, 4, '0', STR_PAD_LEFT);

            // Deduct paidNow from wallet if paidNow > 0
            if ($paidNow > 0 && $paymentMethodId) {
                $pm = PaymentMethod::find($paymentMethodId);
                $pmName = $pm?->name ?? 'Payment Method';
                $description = 'Purchase Payment for Preorder ' . $preorder->reference_no . ' via ' . $pmName;

                $this->balanceService->debitUserWallet($userId, $paidNow, $description, 'purchase_conversion', (int) $preorder->id);
            }

            $assignment = ToolMaterialAssignment::create([
                'preorder_id' => $preorder->id,
                'tool_material_id' => $preorder->tool_material_id,
                'vendor_id' => $vendorId,
                'reference_no' => $referenceNo,
                'status' => 'transferred', // Stock effective
                'handled_by' => $userId,
                'transfer_type' => 'purchase',
                'transaction_type' => 'purchase',
                'source_type' => 'vendor',
                'destination_type' => 'office',
                'quantity' => $quantity,
                'unit' => $preorder->unit,
                'rate' => $rate,
                'amount' => $totalAmount,
                'advance_amount' => $totalAdvance,
                'payment_method_id' => $paymentMethodId,
                'transferred_at' => $transferredAt,
                'notes' => 'Converted from Preorder ' . $preorder->reference_no . '. Total Advance: ' . number_format($totalAdvance, 2) . '. Paid Now: ' . number_format($paidNow, 2) . '. Balance Due: ' . number_format($balanceDue, 2) . '. ' . ($notes ?? ''),
            ]);

            // Create Delivery entry
            $nextDeliveryCount = $preorder->deliveries()->count() + 1;
            PreorderDelivery::create([
                'preorder_id' => $preorder->id,
                'delivery_number' => 'DEL-' . $preorder->reference_no . '-' . $nextDeliveryCount,
                'quantity' => $quantity,
                'delivery_date' => $transferredAt,
                'received_by' => $userId,
                'notes' => 'Converted to Purchase ' . $referenceNo,
                'assignment_id' => $assignment->id,
            ]);

            $totalDelivered = $preorder->totalDeliveredQuantity();
            $orderedQty = (float) $preorder->quantity;
            $newStatus = ($totalDelivered >= $orderedQty && $orderedQty > 0) ? Preorder::STATUS_DELIVERED : Preorder::STATUS_PARTIALLY_DELIVERED;

            $preorder->vendor_id = $vendorId;
            $preorder->rate = $rate;
            $preorder->status = $newStatus;
            $preorder->purchase_date = $transferredAt;
            $preorder->updated_by = $userId;
            $preorder->save();

            $preorder->recalculateStatuses();

            PreorderStatusHistory::create([
                'preorder_id' => $preorder->id,
                'from_status' => $preorder->getOriginal('status'),
                'to_status' => $newStatus,
                'changed_by' => $userId,
                'notes' => 'Converted to Purchase ' . $referenceNo,
            ]);

            $this->logAudit($preorder->id, 'converted_to_purchase', 'Preorder converted to Purchase ' . $referenceNo, $userId, [
                'assignment_id' => $assignment->id,
                'reference_no' => $referenceNo,
            ]);

            return $assignment;
        });
    }

    public function uploadDocument(Preorder $preorder, UploadedFile $file, string $type, string $title, int $userId): PreorderDocument
    {
        $path = $file->store('preorder-documents', 'public');
        $size = $file->getSize();

        $doc = PreorderDocument::create([
            'preorder_id' => $preorder->id,
            'document_type' => $type,
            'title' => $title,
            'file_path' => $path,
            'file_size' => $size,
            'uploaded_by' => $userId,
        ]);

        $this->logAudit($preorder->id, 'document_uploaded', 'Document uploaded: ' . $title, $userId, ['doc_id' => $doc->id]);

        return $doc;
    }

    public function logAudit(int $preorderId, string $action, string $description, int $userId, ?array $metadata = null): PreorderAuditLog
    {
        return PreorderAuditLog::create([
            'preorder_id' => $preorderId,
            'action' => $action,
            'description' => $description,
            'performed_by' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function nextReferenceNumber(): string
    {
        $nextId = ((int) Preorder::query()->max('id')) + 1;

        do {
            $reference = 'PRE-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (Preorder::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
