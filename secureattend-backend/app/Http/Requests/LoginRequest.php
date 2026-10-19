<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required_without:username', 'email', 'exists:users,email'],
            'username' => ['required_without:email', 'string', 'exists:users,username'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Email atau username wajib diisi.',
            'username.required_without' => 'Email atau username wajib diisi.',
            'email.exists' => 'Akun dengan email tersebut tidak ditemukan.',
            'username.exists' => 'Akun dengan username tersebut tidak ditemukan.',
            'password.required' => 'Password wajib diisi.',
        ];
    }
}
