<?php

namespace App\Actions\Jetstream;

use App\Mail\TeamMemberAddedNotification;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;
use OwenIt\Auditing\Models\Audit;

class AddTeamMember implements AddsTeamMembers
{
    /**
     * Add a new team member to the given team.
     */
    public function add(User $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        $newTeamMember = Jetstream::findUserByEmailOrFail($email);

        AddingTeamMember::dispatch($team, $newTeamMember);

        $team->users()->attach(
            $newTeamMember, ['role' => $role]
        );

        $audit = new Audit([
            'user_type' => 'App\Models\User',
            'user_id' => auth()->id(),
            'event' => 'team updated',
            'auditable_id' => $team->id,
            'auditable_type' => get_class($team),
            'new_values' => ['message' => $newTeamMember->name.' has been invited to '.$team->name],
        ]);

        $audit->save();

        TeamMemberAdded::dispatch($team, $newTeamMember);

        // if there is no invitation mode active, send notification email
        // to the user who has been added to the team
        if (! Features::sendsTeamInvitations()) {
            try {
                // send Email to the new team member;
                // note that $project is not null, because
                // teams cannot be created without a project,
                // as defined in App\Actions\Jetstream\CreateTeam
                $project = $team->projects()->first();
                Mail::to($newTeamMember->email)->send(new TeamMemberAddedNotification($team, $newTeamMember, $user, $project));
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                Log::error('Failed to send team member added notification: '.$e->getMessage());
            }
        }
    }

    /**
     * Validate the add member operation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules(), [
            'email.exists' => __('We were unable to find a registered user with this email address.'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for adding a team member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function rules(): array
    {
        return array_filter([
            'email' => ['required', 'email', 'exists:users'],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role]
                : null,
        ]);
    }

    /**
     * Ensure that the user is not already on the team.
     */
    protected function ensureUserIsNotAlreadyOnTeam(Team $team, string $email): Closure
    {
        return function ($validator) use ($team, $email) {
            $validator->errors()->addIf(
                $team->hasUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }
}
