<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $leaveRequestsQuery = LeaveRequest::with(['leaveType', 'user', 'approvedBy'])
            ->orderByDesc('id');

        $status = $request->string('status')->toString();
        if ($status !== '') {
            $leaveRequestsQuery->where('status', $status);
        }

        $leaveRequests = $leaveRequestsQuery->paginate(12)->withQueryString();
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();

        return view('pages.leave-requests.index', compact('leaveRequests', 'leaveTypes'));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['leaveType', 'user', 'approvedBy']);
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();

        return view('pages.leave-requests.details', compact('leaveRequest', 'leaveTypes'));
    }
    public function create()
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $leaveTypes = LeaveType::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.leave-requests.create', compact('users', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leave-requests', 'public');
        }

        LeaveRequest::query()->create([
            'user_id' => (int) $validated['user_id'],
            'leave_type_id' => (int) $validated['leave_type_id'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'remarks' => $validated['remarks'] ?? null,
            'document' => $documentPath ? '/storage/'.$documentPath : null,
            'status' => 'pending',
            'created_by_id' => auth()->id(),
        ]);

        return redirect()->route('leaveRequests.index')->with('success', 'Leave request created successfully.');
    }

    public function approveOrReject(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless(auth()->user()?->hasPermission('leave-requests-edit'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'approverRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'This leave request is already processed.');
        }

        $leaveRequest->status = $validated['status'];
        $leaveRequest->approved_by_id = auth()->id();
        $leaveRequest->approved_at = now();
        $leaveRequest->approver_remarks = $validated['approverRemarks'] ?? null;
        $leaveRequest->save();

        return redirect()->route('leaveRequests.index')->with('success', 'Leave request updated successfully.');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be deleted.');
        }

        $leaveRequest->delete();

        return redirect()->route('leaveRequests.index')->with('success', 'Leave request deleted successfully.');
    }
}
