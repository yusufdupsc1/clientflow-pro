<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {to?}';

    protected $description = 'Send a simple test email to verify mail delivery';

    public function handle(): int
    {
        $to = $this->argument('to') ?? config('mail.from.address');

        if (! $to) {
            $this->error('No recipient specified and MAIL_FROM_ADDRESS is not set.');
            return self::FAILURE;
        }

        try {
            Mail::raw('Clientflow mail test: if you read this, SMTP works.', function ($message) use ($to) {
                $message->to($to)->subject('Clientflow Mail Test');
            });
        } catch (\Throwable $e) {
            $this->error('Mail send failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info("Mail sent to {$to}");
        return self::SUCCESS;
    }
}
