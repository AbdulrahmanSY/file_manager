<?php

namespace App\Http\Requests\FileRequest;

use App\Rules\UserAttachedToRepo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ValidateRepoRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'repo_id'=>['required',new UserAttachedToRepo(Auth::user()->id),Rule::exists('repos','id')->whereNull('deleted_at')]
        ];
    }
    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();
        throw new HttpResponseException($this->badRequestResponse('Bad input', $errors));
    }
}
