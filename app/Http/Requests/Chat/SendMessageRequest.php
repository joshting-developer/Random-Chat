<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * 是否允許送出訊息
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
            'message' => ['required', 'string', 'max:1000'],
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
            'message.required' => '請輸入訊息內容。',
        ];
    }

    /**
     * 取得路由參數進行驗證
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'room_key' => $this->route('roomKey'),
        ]);
    }
}
