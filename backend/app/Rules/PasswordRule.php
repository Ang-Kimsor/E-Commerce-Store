<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("The {$attribute} must be a string.");
            return;
        }

        $error = self::getValidationError($value);
        if ($error !== null) {
            $fail($error);
        }
    }

    /**
     * Check if a password meets the strength criteria:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     */
    public static function isValid(?string $password): bool
    {
        return self::getValidationError($password) === null;
    }

    /**
     * Return validation error message if invalid, or null if valid.
     */
    public static function getValidationError(?string $password): ?string
    {
        if ($password === null || strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }

        return null;
    }
}
