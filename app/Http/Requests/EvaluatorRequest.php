<?php

namespace App\Http\Requests;

use App\Enums\EvaluatorTypeEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

class EvaluatorRequest extends FormRequest
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
            "periodId" => ["required", "integer", "exists:periods,id"],
            "type" => ["required", "string", new Enum(EvaluatorTypeEnum::class)],
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
