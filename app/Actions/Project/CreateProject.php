<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Support\Activity\ActivityLogger;

class CreateProject
{
    public function handle(array $data): Project
    {
        $project = Project::create($data);

        ActivityLogger::log($project, 'projects.created');

        return $project;
    }
}
