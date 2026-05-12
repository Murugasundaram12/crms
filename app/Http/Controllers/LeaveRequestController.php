<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
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

        return view('pages.leave-requests', compact('leaveRequests', 'leaveTypes'));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['leaveType', 'user', 'approvedBy']);
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();

        return view('pages.leave-requests-details', compact('leaveRequest', 'leaveTypes'));
    }

    public function approveOrReject(Request $request, LeaveRequest $leaveRequest)
    {
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
