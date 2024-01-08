<?php

namespace App\Http\Requests\FileRequest;

use App\Rules\UserAttachedToRepo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckInOutRequest extends FormRequest
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
            'repo_id'=>['required',Rule::exists('repos','id')->whereNull('deleted_at'),new UserAttachedToRepo(Auth::user()->id)],
            'file_id' => [
                'required',
                'array',
                Rule::exists('files', 'id')->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->whereIn('id', $this->input('file_id'));
                    }),
                 ],
            ];
    }
    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();
        throw new HttpResponseException($this->badRequestResponse('Bad input', $errors));
    }
}
