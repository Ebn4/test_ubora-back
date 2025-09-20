<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthenticationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "cuid" => ["required", "string"],
            "password" => ["required", "string"],
        ];
    }


    public function failedValidation(Validator $validator)
    {
        return throw new HttpResponseException(
            response()->json(data: [
                "errors" => $validator->errors()
            ], status: 400)
        );
    }

}
