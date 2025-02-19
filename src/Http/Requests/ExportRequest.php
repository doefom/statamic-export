<?php

namespace Doefom\StatamicExport\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:collections,users',
            'collection_handle' => 'required_if:type,collections|string|nullable',
            'file_type' => 'nullable|string|in:xlsx,csv,tsv,ods,xls,html',
            'excluded_fields' => 'nullable|array',
            'headers' => 'nullable|boolean',
        ];
    }
}
