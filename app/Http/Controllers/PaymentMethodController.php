<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $query = PaymentMethod::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('active_status', true);
            } elseif ($status === 'inactive') {
                $query->where('active_status', false);
            }
        }

        $paymentMethods = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate((int) $request->input('paginate', 15))
            ->withQueryString();

        return view('pages.payment_methods.index', compact('paymentMethods'));
    }

    public function create(): View
    {
        return view('pages.payment_methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:payment_methods,name'],
            'code' => ['nullable', 'string', 'max:100', 'unique:payment_methods,code'],
            'type' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active_status' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = filled($validated['code'] ?? null)
            ? Str::upper(Str::slug($validated['code'], '_'))
            : Str::upper(Str::slug($validated['name'], '_'));

        $validated['active_status'] = $request->boolean('active_status', true);
        $validated['created_by'] = Auth::id();
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        PaymentMethod::create($validated);

        return redirect()->route('payment-methods.index')->with('success', 'Payment method created successfully.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('pages.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'name')->ignore($paymentMethod->id)],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('payment_methods', 'code')->ignore($paymentMethod->id)],
            'type' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active_status' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = filled($validated['code'] ?? null)
            ? Str::upper(Str::slug($validated['code'], '_'))
            : Str::upper(Str::slug($validated['name'], '_'));

        $validated['active_status'] = $request->boolean('active_status', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $paymentMethod->update($validated);

        return redirect()->route('payment-methods.index')->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $isUsed = DB::table('variations')->where('payment_method_id', $paymentMethod->id)->exists()
            || DB::table('employee_salaries')->where('payment_method_id', $paymentMethod->id)->exists()
            || DB::table('labour_salaries')->where('payment_method_id', $paymentMethod->id)->exists()
            || DB::table('preorders')->where('payment_method_id', $paymentMethod->id)->exists()
            || DB::table('tool_material_assignments')->where('payment_method_id', $paymentMethod->id)->exists()
            || DB::table('wallet')->where('payment_method_id', $paymentMethod->id)->exists();

        if ($isUsed) {
            $paymentMethod->update(['active_status' => false]);

            return redirect()->route('payment-methods.index')
                ->with('error', 'Cannot delete payment method because it has existing transactions. It has been deactivated instead.');
        }

        $paymentMethod->delete();

        return redirect()->route('payment-methods.index')->with('success', 'Payment method deleted successfully.');
    }

    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update(['active_status' => ! $paymentMethod->active_status]);

        $statusLabel = $paymentMethod->active_status ? 'activated' : 'deactivated';

        return redirect()->route('payment-methods.index')->with('success', "Payment method {$statusLabel} successfully.");
    }
}
