<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // Build the client list query with the requested filters.
        $clientQuery = Client::query();
        $this->applySearchFilter($clientQuery, $request);
        $this->applyStatusFilter($clientQuery, $request);

        // Load relationship counts for the listing page.
        $clients = $clientQuery
            ->withCount(['projects', 'payments'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.clients.index', compact('clients'));
    }

    public function store(Request $request)
    {
        // Validate the form data before creating a new client.
        $validatedData = $this->validateClientData($request);

        // Save the new client record.
        Client::create($validatedData);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        return redirect()->route('clients.index', ['highlight' => $client->id]);
    }

    public function edit(Client $client)
    {
        // Reuse the main listing page and open the selected client for editing.
        return redirect()->route('clients.index', ['edit' => $client->id]);
    }

    public function update(Request $request, Client $client)
    {
        // Validate the form data before updating the client.
        $validatedData = $this->validateClientData($request, $client);

        // Update the existing client record.
        $client->update($validatedData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        // Delete the selected client record.
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    private function applySearchFilter($clientQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $clientQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('company_name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('phone', 'like', "%{$searchTerm}%");
        });
    }

    private function applyStatusFilter($clientQuery, Request $request): void
    {
        $status = $request->string('status')->toString();

        if ($status === '') {
            return;
        }

        $clientQuery->where('status', $status);
    }

    private function validateClientData(Request $request, ?Client $client = null): array
    {
        // Validate each field and keep the rules close to the controller action.
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client?->id),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['enquiry', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
