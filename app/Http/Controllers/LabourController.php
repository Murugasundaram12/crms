<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LabourController extends Controller
{
    public function index(Request $request)
    {
        // Build the labour query and apply the requested search and filters.
        $labourQuery = Labour::with('labourRole');
        $this->applySearchFilter($labourQuery, $request);
        $this->applyRoleFilter($labourQuery, $request);
        $this->applyGenderFilter($labourQuery, $request);

        // Load the list results and the role filter options.
        $labours = $labourQuery->latest()->paginate(10)->withQueryString();
        $labourRoles = LabourRole::orderBy('name')->get();

        return view('pages.labours.index', compact('labours', 'labourRoles'));
    }

    public function create()
    {
        // Load all labour roles for the dropdown on the create form.
        $labourRoles = LabourRole::orderBy('name')->get();

        return view('pages.labours.create', compact('labourRoles'));
    }

    public function store(Request $request)
    {
        // Validate the form before creating a new labour record.
        $validatedData = $this->validateLabourData($request);

        // Save the uploaded government photo when one is provided.
        $validatedData['government_photo'] = $this->storeGovernmentPhoto($request);

        // Save the new labour record.
        Labour::create($validatedData);

        return redirect()->route('labours.index')->with('success', 'Labour created successfully.');
    }

    public function edit($id)
    {
        // Load the labour record and all labour roles for the edit form.
        $labour = Labour::findOrFail($id);
        $labourRoles = LabourRole::orderBy('name')->get();

        return view('pages.labours.edit', compact('labour', 'labourRoles'));
    }

    public function update(Request $request, $id)
    {
        // Load the selected labour record before updating it.
        $labour = Labour::findOrFail($id);

        // Validate the submitted values.
        $validatedData = $this->validateLabourData($request);

        // Replace the stored photo only when a new file is uploaded.
        $validatedData['government_photo'] = $this->storeGovernmentPhoto($request, $labour->government_photo);

        if ($validatedData['government_photo'] === null) {
            $validatedData['government_photo'] = $labour->government_photo;
        }

        // Save the updated labour record.
        $labour->update($validatedData);

        return redirect()->route('labours.index')->with('success', 'Labour updated successfully.');
    }

    public function destroy($id)
    {
        // Load the labour record before deleting it.
        $labour = Labour::findOrFail($id);

        // Delete the stored government photo when it exists.
        if ($labour->government_photo) {
            Storage::disk('public')->delete($labour->government_photo);
        }

        // Delete the labour record.
        $labour->delete();

        return redirect()->route('labours.index')->with('success', 'Labour deleted successfully.');
    }

    private function applySearchFilter($labourQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $labourQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('job_title', 'like', "%{$searchTerm}%")
                ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                ->orWhere('gender', 'like', "%{$searchTerm}%")
                ->orWhere('salary', 'like', "%{$searchTerm}%")
                ->orWhereHas('labourRole', function ($labourRoleQuery) use ($searchTerm) {
                    $labourRoleQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    private function applyRoleFilter($labourQuery, Request $request): void
    {
        $labourRoleId = $request->input('labour_role_id');

        if (! $labourRoleId) {
            return;
        }

        $labourQuery->where('labour_role_id', $labourRoleId);
    }

    private function applyGenderFilter($labourQuery, Request $request): void
    {
        $gender = $request->string('gender')->toString();

        if ($gender === '') {
            return;
        }

        $labourQuery->where('gender', $gender);
    }

    private function validateLabourData(Request $request): array
    {
        // Validate each field before saving the labour record.
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'labour_role_id' => ['required', 'exists:labour_roles,id'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'salary' => ['required', 'numeric'],
            'government_photo' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function storeGovernmentPhoto(Request $request, ?string $existingPhotoPath = null): ?string
    {
        if (! $request->hasFile('government_photo')) {
            return null;
        }

        if ($existingPhotoPath) {
            Storage::disk('public')->delete($existingPhotoPath);
        }

        return $request->file('government_photo')->store('government-photos', 'public');
    }
}
