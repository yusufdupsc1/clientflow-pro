<?php

namespace App\Actions\Client;

use App\Models\Client;

class DeleteClient
{
    public function handle(Client $client): void
    {
        $client->delete();
    }
}
