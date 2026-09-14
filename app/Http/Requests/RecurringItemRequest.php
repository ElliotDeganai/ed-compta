<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage recurring');
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
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ];
    }
}
