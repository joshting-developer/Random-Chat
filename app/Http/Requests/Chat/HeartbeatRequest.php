<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    /**
     * 是否允許送出心跳
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
            'room_key' => ['required', 'uuid'],
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
            'room_key.required' => '請提供房間識別碼。',
            'user_key.required' => '請提供使用者識別碼。',
        ];
    }
}
