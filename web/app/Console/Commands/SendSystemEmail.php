<?php

namespace App\Console\Commands;

use App\Mail\SystemMessage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSystemEmail extends Command
{
    protected $signature = 'email:system';

    protected $description = 'Send a system email to specified recipients';

    public function handle()
    {
        // Initial warning with ASCII art
        $warningArt = <<<'ASCII'

 █     █░ ▄▄▄       ██▀███   ███▄    █  ██▓ ███▄    █   ▄████
▓█░ █ ░█░▒████▄    ▓██ ▒ ██▒ ██ ▀█   █ ▓██▒ ██ ▀█   █  ██▒ ▀█▒
▒█░ █ ░█ ▒██  ▀█▄  ▓██ ░▄█ ▒▓██  ▀█ ██▒▒██▒▓██  ▀█ ██▒▒██░▄▄▄░
░█░ █ ░█ ░██▄▄▄▄██ ▒██▀▀█▄  ▓██▒  ▐▌██▒░██░▓██▒  ▐▌██▒░▓█  ██▓
░░██▒██▓  ▓█   ▓██▒░██▓ ▒██▒▒██░   ▓██░░██░▒██░   ▓██░░▒▓███▀▒
░ ▓░▒ ▒   ▒▒   ▓▒█░░ ▒▓ ░▒▓░░ ▒░   ▒ ▒ ░▓  ░ ▒░   ▒ ▒  ░▒   ▒
  ▒ ░ ░    ▒   ▒▒ ░  ░▒ ░ ▒░░ ░░   ░ ▒░ ▒ ░░ ░░   ░ ▒░  ░   ░
  ░   ░    ░   ▒     ░░   ░    ░   ░ ░  ▒ ░   ░   ░ ░ ░ ░   ░
    ░          ░  ░   ░              ░  ░           ░       ░

ASCII;

        $this->newLine(2);
        $this->error($warningArt);
        $this->error('╔════════════════════════════════════════════════════════════════════╗');
        $this->error('║                    IMPORTANT SMTP WARNING                          ║');
        $this->error('╠════════════════════════════════════════════════════════════════════╣');
        $this->error('║ This command uses your configured SMTP connection.                 ║');
        $this->error('║ If you have PRODUCTION SMTP in your .env file,                     ║');
        $this->error('║ selecting "all users" will send to EVERYONE in the database!       ║');
        $this->error('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if (! $this->confirm('Would you like to proceed?', false)) {
            $this->info('Operation cancelled');

            return 0;
        }

        // Get reply-to address
        $replyTo = $this->ask('Enter reply-to email address');

        if (! filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');

            return 1;
        }

        // Get message content (multiline)
        $this->info('Enter your message (type "end" on a new line to finish):');
        $message = '';
        while (true) {
            $line = $this->ask('');
            if (trim($line) === 'end') {
                break;
            }
            $message .= $line."\n";
        }

        // Offer preview first
        if ($this->confirm('Would you like to send a preview to your email?')) {
            $previewEmail = $this->ask('Enter your email for preview');
            if (filter_var($previewEmail, FILTER_VALIDATE_EMAIL)) {
                $email = new SystemMessage($message, $replyTo);
                Mail::to($previewEmail)->send($email);
                $this->info('Preview sent to '.$previewEmail);

                if (! $this->confirm('Did you receive and check the preview? Would you like to continue?', false)) {
                    $this->info('Operation cancelled');

                    return 0;
                }
            }
        }

        // Ask for sending mode
        $sendMode = $this->choice(
            'How would you like to send this message?',
            ['single user', 'all users'],
            'single user'
        );

        if ($sendMode === 'single user') {
            $recipient = $this->ask('Enter recipient email');

            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email address');

                return 1;
            }

            try {
                $email = new SystemMessage($message, $replyTo);
                Mail::bcc($recipient)->send($email);
                $this->info('Message sent successfully!');
            } catch (\Exception $e) {
                $this->error('Failed to send email: '.$e->getMessage());

                return 1;
            }
        } else {
            // Safety check for mass email
            $num1 = rand(10, 20);
            $num2 = rand(5, 10);
            $answer = $this->ask("Safety check: What is $num1 + $num2? (Enter the number)");

            if ((int) $answer !== ($num1 + $num2)) {
                $this->error('Incorrect answer - operation cancelled for safety');

                return 1;
            }

            $userCount = User::count();
            if (! $this->confirm("Are you absolutely sure you want to send this message to ALL $userCount users?", false)) {
                $this->info('Operation cancelled');

                return 0;
            }

            try {
                $users = User::pluck('email');
                $email = new SystemMessage($message, $replyTo);
                Mail::bcc($users)->send($email);
                $this->info("Message sent successfully to $userCount users!");
            } catch (\Exception $e) {
                $this->error('Failed to send email: '.$e->getMessage());

                return 1;
            }
        }

        return 0;
    }
}
