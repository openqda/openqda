{{ __('Dear :user,', ['user' => $user->name]) }}

{{ __(':by added you to the ":team" team!', ['team' => $team->name, 'by' => $by->name, 'user' => $user->name]) }}

{{ __('You can access the team by clicking the button below:') }}

{{ __('Access Team') }}: {{ route('project.show', $project->id) }}

{{ __('If you did not expect to receive an invitation to this team, you can leave it at any time in your project settings.') }}

{{ __('Best regards,') }}
{{ config('app.name') }}
