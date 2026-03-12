<?php

namespace App\Concerns;

use App\Models\Blacklist;
use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string|Closure>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'full_name' => $this->fullNameRules(),
            'email' => $this->emailRules($userId),
            'contact_number' => $this->contactNumberRules(),
            'emergency_contact' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * Get the validation rules used to validate full names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function fullNameRules(): array
    {
        return ['required', 'string', 'max:150'];
    }

    /**
     * Get the validation rules used to validate contact numbers.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function contactNumberRules(): array
    {
        return ['required', 'string', 'max:20'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string|Closure>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:100',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
            function (string $attribute, mixed $value, Closure $fail) {
                if (Blacklist::where('email', $value)->exists()) {
                    $fail('This email address has been banned from the system.');
                }
            },
        ];
    }
}
