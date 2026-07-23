<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\ToolMaterial;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreorderReportController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'summary');

        $query = Preorder::query()->with(['toolMaterial', 'vendor', 'paymentMethod', 'creator', 'approver', 'advances', 'deliveries']);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('tool_material_id')) {
            $query->where('tool_material_id', $request->integer('tool_material_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('preorder_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('preorder_date', '<=', $request->date('date_to')->toDateString());
        }

        $preorders = (clone $query)->latest()->get();

        $metrics = [
            'total_preorders' => $preorders->count(),
            'pending_approval' => $preorders->whereIn('status', ['draft', 'pending_approval'])->count(),
            'approved' => $preorders->where('status', 'approved')->count(),
            'ordered' => $preorders->where('status', 'ordered')->count(),
            'partially_delivered' => $preorders->where('status', 'partially_delivered')->count(),
            'delivered' => $preorders->where('status', 'delivered')->count(),
            'cancelled' => $preorders->where('status', 'cancelled')->count(),
            'total_estimated' => $preorders->sum('estimated_amount'),
            'total_amount' => $preorders->sum('total_amount'),
            'total_advance_paid' => $preorders->sum('advance_amount'),
            'total_pending_balance' => $preorders->sum('remaining_amount'),
        ];

        // Vendor-wise aggregation
        $vendorSummary = $preorders->groupBy('vendor_id')->map(function ($group) {
            $vendor = $group->first()->vendor;
            return [
                'vendor_name' => $vendor?->name ?? 'Unassigned',
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'advance_paid' => $group->sum('advance_amount'),
                'pending_balance' => $group->sum('remaining_amount'),
            ];
        });

        // Material-wise aggregation
        $materialSummary = $preorders->groupBy('tool_material_id')->map(function ($group) {
            $material = $group->first()->toolMaterial;
            return [
                'material_name' => $material?->name ?? 'Unknown',
                'sku' => $material?->sku ?? '-',
                'count' => $group->count(),
                'total_qty' => $group->sum('quantity'),
                'delivered_qty' => $group->sum(fn($p) => $p->totalDeliveredQuantity()),
                'remaining_qty' => $group->sum(fn($p) => $p->remainingQuantity()),
                'total_amount' => $group->sum('total_amount'),
            ];
        });

        return view('pages.preorders.reports', [
            'tab' => $tab,
            'preorders' => $preorders,
            'metrics' => $metrics,
            'vendorSummary' => $vendorSummary,
            'materialSummary' => $materialSummary,
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'toolsMaterials' => ToolMaterial::query()->orderBy('name')->get(),
        ]);
    }
}
