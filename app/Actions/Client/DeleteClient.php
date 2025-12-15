<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Support\Activity\ActivityLogger;

class DeleteClient
{
    public function handle(Client $client): void
    {
        ActivityLogger::log($client, 'clients.deleted');
        $client->delete();
    }
}
