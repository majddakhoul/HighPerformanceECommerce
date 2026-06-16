<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'cost'        => 'sometimes|numeric|min:0',
            'category'    => 'nullable|string|max:100',
            'image'       => 'nullable|string|max:255',
        ];
    }

    public function validatedWithId(): array
    {
        return array_merge($this->validated(), [
            'id' => $this->route('id'),
        ]);
    }
}
