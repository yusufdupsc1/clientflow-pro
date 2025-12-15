<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Support\Activity\ActivityLogger;

class DeleteProject
{
    public function handle(Project $project): void
    {
        ActivityLogger::log($project, 'projects.deleted');
        $project->delete();
    }
}
