<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimulationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage transactions');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'occurred_on' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_enabled' => ['boolean'],
        ];
    }
}
