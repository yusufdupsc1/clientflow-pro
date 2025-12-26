<?php

namespace App\Mail;

use App\Models\Organization;
<<<<<<< HEAD
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
=======
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
use Illuminate\Queue\SerializesModels;

class RoleChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

<<<<<<< HEAD
    public function __construct(public Organization $organization, public string $role)
    {
    }

    public function build(): self
    {
        return $this->subject('Your role was updated in '.$this->organization->name)
            ->view('emails.role-changed', [
                'organization' => $this->organization,
                'role' => $this->role,
            ]);
=======
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
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    }
}
