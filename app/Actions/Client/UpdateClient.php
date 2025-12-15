<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Support\Activity\ActivityLogger;

class UpdateClient
{
    public function handle(Client $client, array $data): Client
    {
        $client->update($data);

        ActivityLogger::log($client, 'clients.updated');

        return $client;
    }
}
