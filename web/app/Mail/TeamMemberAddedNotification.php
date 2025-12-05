<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamMemberAddedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $team;

    public $user;

    public $by;

    public $project;

    public function __construct(Team $team, User $user, User $by, Project $project)
    {
        $this->team = $team;
        $this->user = $user;
        $this->by = $by;
        $this->project = $project;
    }

    public function envelope(): Envelope
    {
        $subject = '['.config('app.name').']: you have been added to the team "'.$this->team->name.'"';

        return new Envelope(
            from: new Address('no-reply@openqda.org', 'OpenQDA System'),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-added',
            with: [
                'team' => $this->team,
                'user' => $this->user,
                'by' => $this->by,
                'project' => $this->project,
            ],
        );
    }
}
