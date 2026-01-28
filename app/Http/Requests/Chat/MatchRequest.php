<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class MatchRequest extends FormRequest
{
    /**
     * 是否允許開始配對
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 取得驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_key' => ['required', 'uuid'],
        ];
    }

    /**
     * 取得驗證錯誤訊息
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_key.required' => '請提供使用者識別碼。',
        ];
    }
}
