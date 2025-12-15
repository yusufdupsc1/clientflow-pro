<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Support\Activity\ActivityLogger;

class CreateClient
{
    public function handle(array $data): Client
    {
        $client = Client::create($data);

        ActivityLogger::log($client, 'clients.created');

        return $client;
    }
}
