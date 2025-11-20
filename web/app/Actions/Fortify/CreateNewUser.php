<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Services\ResearchConsentService;
use GrantHolle\Altcha\Rules\ValidAltcha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Log::info('Creating new user with email: '.$input['email']);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
            // 'research' is optional and can be true or false
            'research' => ['sometimes', 'boolean'],
            'password' => $this->passwordRules(),
        ];

        // Apply the ValidAltcha rule only if not in testing environment
        if (app()->environment('testing')) {
            $rules['altcha'] = ['required', 'string'];
        } else {
            $rules['altcha'] = ['required', new ValidAltcha];
        }

        Validator::make($input, $rules)->validate();
        $timestamp = now();

        return DB::transaction(function () use ($input, $timestamp) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'terms_consent' => $timestamp,
                'privacy_consent' => $timestamp,
            ]), function (User $user) use ($input, $timestamp) {
                $this->createTeam($user);
                $user->createAudit(User::AUDIT_TERMS_CONSENTED, ['terms_consent' => $timestamp]);
                $user->createAudit(User::AUDIT_PRIVACY_CONSENTED, ['privacy_consent' => $timestamp]);

                if ($input['research'] ?? false) {
                    try {
                        $consent = app(ResearchConsentService::class);
                        $consent->sendResearchConfirmation($user);
                    } catch (\Exception $e) {
                        // fail silently to not block user creation
                        Log::error('Failed to send research consent confirmation email to user ID '.$user->id.': '.$e->getMessage());
                    }
                }
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
