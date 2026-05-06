<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'quotation_date' => 'required|date',
            'quotation_title' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_address' => 'required|string|max:1000',
            'main_title' => 'nullable|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'proposal_content' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(fn($query) => $query->where('client_id', $this->input('client_id'))),
            ],
            'validity_days' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:1|max:3650',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.main_title' => 'required|string|max:255',
            'items.*.rows' => 'required|array|min:1',
            'items.*.rows.*.description' => 'nullable|string|max:1000',
            'items.*.rows.*.nos' => 'nullable|numeric|min:0',
            'items.*.rows.*.length' => 'nullable|numeric|min:0',
            'items.*.rows.*.breadth' => 'nullable|numeric|min:0',
            'items.*.rows.*.depth' => 'nullable|numeric|min:0',
            'items.*.rows.*.quantity' => 'required|numeric|min:0',
            'items.*.rows.*.unit' => 'nullable|string|max:50',
            'items.*.rows.*.price' => 'required|numeric|min:0',
            'items.*.rows.*.amount' => 'required|numeric|min:0',
        ];
    }
}
