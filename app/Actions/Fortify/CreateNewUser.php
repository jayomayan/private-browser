<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users',
            function ($attribute, $value, $fail) {
                // Restrict to specific domain(s)
                if (!str_ends_with($value, '@frontiertowersphilippines.com')) {
                    $fail('Only @frontiertowersphilippines.com email addresses are allowed.');
                }
            },
        ], [
            'name.required' => __('Please provide your full name.'),
            'name.string' => __('Your name should be a string.'),
            'name.max' => __('Your name should not exceed 255 characters.'),
            'email.required' => __('Please provide your email address.'),],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
