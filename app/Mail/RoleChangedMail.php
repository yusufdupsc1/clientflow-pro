<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoleChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
    }
}
