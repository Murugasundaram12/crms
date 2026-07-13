<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocationTracking;
use App\Models\MainCategory;
use App\Models\MobileApiToken;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobileLeaveMasterEndpoints
{
    public function leaveOptions()
    {
        return response()->json([
            'leave_types' => LeaveType::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function leaveRequests(Request $request)
    {
        $canListAll = $this->canUseApiPermission($request->user(), 'leave-requests-list');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LeaveRequest::query()
            ->with(['leaveType', 'user', 'approvedBy'])
            ->latest('id');

        if (! $canListAll) {
            $query->where('user_id', $request->user()->id);
        }

        $query
            ->when($validated['status'] ?? null, fn($q, string $status) => $q->where('status', $status))
            ->when($validated['q'] ?? null, function ($q, string $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('remarks', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('leaveType', fn($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"));
                });
            });

        if (! blank($validated['date_from'] ?? null)) {
            $query->whereDate('from_date', '>=', $request->date('date_from')->toDateString());
        }

        if (! blank($validated['date_to'] ?? null)) {
            $query->whereDate('to_date', '<=', $request->date('date_to')->toDateString());
        }

        $leaveRequests = $query->paginate((int) ($validated['per_page'] ?? 15));
        $leaveRequests->setCollection($leaveRequests->getCollection()->map(fn(LeaveRequest $leaveRequest) => $this->leaveRequestPayload($leaveRequest)));

        return response()->json($leaveRequests);
    }

    public function storeLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('status', 'active'),
            ],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'leave_type_id.exists' => 'Selected leave type is not available. Call /leave-requests/options and use an active leave type id.',
        ]);

        $targetUserId = (int) ($validated['user_id'] ?? $request->user()->id);
        if ($targetUserId !== (int) $request->user()->id && ! $this->canUseApiPermission($request->user(), 'leave-requests-create')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $targetUserId,
            'leave_type_id' => (int) $validated['leave_type_id'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'remarks' => $validated['remarks'] ?? null,
            'status' => 'pending',
            'created_by_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Leave request created successfully.',
            'leave_request' => $this->leaveRequestPayload($leaveRequest->load(['leaveType', 'user', 'approvedBy'])),
        ], 201);
    }

    public function actionLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'leave-requests-edit')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'approver_remarks' => ['nullable', 'string', 'max:1000'],
            'approverRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'This leave request is already processed.'], 409);
        }

        $leaveRequest->update([
            'status' => $validated['status'],
            'approved_by_id' => $request->user()->id,
            'approved_at' => now(),
            'approver_remarks' => $validated['approver_remarks'] ?? $validated['approverRemarks'] ?? null,
        ]);

        return response()->json([
            'message' => 'Leave request updated successfully.',
            'leave_request' => $this->leaveRequestPayload($leaveRequest->fresh(['leaveType', 'user', 'approvedBy'])),
        ]);
    }

    public function deleteLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        if ((int) $leaveRequest->user_id !== (int) $request->user()->id && ! $this->canUseApiPermission($request->user(), 'leave-requests-delete')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending leave requests can be deleted.'], 409);
        }

        $leaveRequest->delete();

        return response()->json(['message' => 'Leave request deleted successfully.']);
    }

    public function categories(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'categories-list')) {
            return $forbidden;
        }

        return response()->json(['data' => Category::query()->with('mainCategory')->orderBy('name')->get()->map(fn(Category $category) => $this->categoryPayload($category))]);
    }

    public function mainCategories(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'main-categories-list')) {
            return $forbidden;
        }

        return response()->json(['data' => MainCategory::query()->with('categories')->orderBy('name')->get()->map(fn(MainCategory $category) => $this->mainCategoryPayload($category))]);
    }

    public function vendors(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'vendors-list')) {
            return $forbidden;
        }

        return response()->json(['data' => Vendor::query()->orderBy('name')->get()->map(fn(Vendor $vendor) => $this->vendorPayload($vendor))]);
    }

    public function labourRoles(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'labour-roles-list')) {
            return $forbidden;
        }

        return response()->json(['data' => LabourRole::query()->orderBy('name')->get()->map(fn(LabourRole $role) => $this->labourRolePayload($role))]);
    }

    public function labours(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'labours-list')) {
            return $forbidden;
        }

        return response()->json(['data' => Labour::query()->with('labourRole')->orderBy('name')->get()->map(fn(Labour $labour) => $this->labourPayload($labour))]);
    }
}

