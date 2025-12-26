<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoleChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organization $organization,
        public string $oldRole,
        public string $newRole
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Role Has Been Updated - ' . $this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.role-changed',
            with: [
                'user' => $this->user,
                'organization' => $this->organization,
                'oldRole' => $this->oldRole,
                'newRole' => $this->newRole,
            ],
        );
    }
}
