<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $templateId = $this->route('templateId');

        return [
            'field_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z_]+$/', // lowercase with underscores only
                'unique:template_fields,field_key,NULL,id,template_id,'.$templateId,
            ],
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|in:text,textarea,date,time,url,email,phone,image,color',
            'field_category' => 'nullable|string|in:couple,event,venue,design,general',
            'placeholder' => 'nullable|string|max:500',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'validation_rules.required' => 'nullable|boolean',
            'validation_rules.min' => 'nullable|integer|min:1',
            'validation_rules.max' => 'nullable|integer|min:1',
            'validation_rules.pattern' => 'nullable|string',
            'help_text' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'field_key.required' => 'Field key is required',
            'field_key.regex' => 'Field key must contain only lowercase letters and underscores',
            'field_key.unique' => 'This field key already exists for this template',
            'field_type.in' => 'Invalid field type selected',
        ];
    }
}
