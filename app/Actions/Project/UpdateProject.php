<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Support\Activity\ActivityLogger;

class UpdateProject
{
    public function handle(Project $project, array $data): Project
    {
        $project->update($data);

        ActivityLogger::log($project, 'projects.updated');

        return $project;
    }
}
