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
            //             'research' => ['required', 'boolean'],
            'password' => $this->passwordRules(),
        ];

        // Apply the ValidAltcha rule only if not in testing environment
        if (app()->environment('testing')) {
            $rules['altcha'] = ['required', 'string'];
        } else {
            $rules['altcha'] = ['required', new ValidAltcha];
        }

        Validator::make($input, $rules)->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) use ($input) {
                $this->createTeam($user);

                Log::info('User wants to participate in research? → '.$input['research']);
                if ($input['research']) {
                    try {
                        $consent = app(ResearchConsentService::class);
                        $consent->sendResearchConfirmation($user);
                    } catch (e) {
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
