<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Project;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        $query = Wallet::query()
            ->where('delete_status', 0)
            ->with(['user', 'client', 'project', 'stage', 'paymentMethod'])
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('current_date', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('current_date', '<=', $request->date('date_to')->toDateString()))
            ->when($request->filled('date_range'), function ($q) use ($request) {
                $range = $request->string('date_range')->toString();
                [$from, $to] = str_contains($range, ' - ')
                    ? array_map('trim', explode(' - ', $range, 2))
                    : [trim($range), null];

                if (! blank($from)) {
                    $q->whereDate('current_date', '>=', Carbon::parse($from)->toDateString());
                }
                if (! blank($to)) {
                    $q->whereDate('current_date', '<=', Carbon::parse($to)->toDateString());
                }
            })
            ->when($request->filled('search'), function ($query) use ($request, $paymentMethods) {
                $search = $request->string('search')->toString();
                $lower = strtolower($search);

                $matchingPaymentMethodIds = $paymentMethods
                    ->filter(fn($pm) => str_contains(strtolower($pm->name), $lower))
                    ->pluck('id')
                    ->all();

                $query->where(function ($q) use ($search, $lower, $matchingPaymentMethodIds) {
                    if (str_contains('credited', $lower)) {
                        $q->orWhere('transfer_type', 0);
                    }
                    if (str_contains('debited', $lower)) {
                        $q->orWhere('transfer_type', 1);
                    }
                    if ($matchingPaymentMethodIds !== []) {
                        $q->orWhereIn('payment_method_id', $matchingPaymentMethodIds);
                    }

                    $q->orWhere('amount', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stage', fn($stageQuery) => $stageQuery->where('stage_name', 'like', "%{$search}%"));
                });
            });

        $creditTotal = (clone $query)->where('transfer_type', 0)->sum('amount');
        $debitTotal = (clone $query)->where('transfer_type', 1)->sum('amount');
        $totalAmount = $creditTotal - $debitTotal;
        $wallets = $query
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 10))
            ->withQueryString();

        return view('pages.wallet.index', [
            'wallets' => $wallets,
            'clients' => Client::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'users' => User::query()
                ->where('status', '!=', 'inactive')
                ->whereKeyNot(Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'wallet']),
            'paymentMethods' => $paymentMethods,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function create()
    {
        return view('pages.wallet.create', [
            'clients' => Client::query()->where('status', '!=', 'inactive')->orderBy('name')->get(),
            'projects' => Project::query()->whereIn('status', ['planning', 'active', 'on_hold'])->orderBy('name')->get(),
            'users' => User::query()->where('status', '!=', 'inactive')->orderBy('name')->get(['id', 'name', 'email', 'wallet']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'stages' => PaymentStage::query()->orderBy('stage_name')->get(),
            'walletBalance' => (float) (Auth::user()->wallet ?? 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'transfer_type' => ['required', 'integer', 'in:0,1'],
            'stage_id' => ['nullable', 'exists:payment_stages,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'current_date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
        ]);

        $amount = (int) $validated['amount'];
        $actor = Auth::user();
        $targetUser = ! blank($validated['user_id'] ?? null)
            ? User::query()->findOrFail((int) $validated['user_id'])
            : $actor;
        $project = Project::query()->findOrFail((int) $validated['project_id']);

        if ((int) $project->client_id !== (int) $validated['client_id']) {
            throw ValidationException::withMessages([
                'project_id' => 'Selected project does not belong to the selected client.',
            ]);
        }

        if ((int) $validated['transfer_type'] === 1 && $amount > (float) ($targetUser->wallet ?? 0)) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is insufficient',
            ]);
        }

        DB::transaction(function () use ($validated, $amount, $targetUser) {
            $dateTime = Carbon::parse($validated['current_date'] . ' ' . ($validated['time'] ?? now()->format('H:i')));
            $description = $validated['description'] ?? null;

            Wallet::query()->create([
                'user_id' => $targetUser->id,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'amount' => $amount,
                'payment_method_id' => $validated['payment_method_id'],
                'transfer_type' => $validated['transfer_type'],
                'stage_id' => $validated['stage_id'] ?? null,
                'description' => $description,
                'current_date' => $dateTime,
                'active_status' => 1,
                'delete_status' => 0,
            ]);

            $balanceService = app(CrmBalanceService::class);

            if ((int) $validated['transfer_type'] === 0) {
                $balanceService->applyProjectIncome((int) $validated['project_id'], $amount);
                $balanceService->adjustUserWallet((int) $targetUser->id, $amount);
                return;
            }

            $balanceService->reverseProjectIncome((int) $validated['project_id'], $amount);
            $balanceService->adjustUserWallet((int) $targetUser->id, -$amount);
        });

        return redirect()->route('wallet.index')->with('success', 'Wallet entry saved successfully.');
    }
}
