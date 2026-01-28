<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRoomRequest extends FormRequest
{
    /**
     * 是否允許離開房間
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
