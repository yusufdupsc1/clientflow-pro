<?php

namespace App\Actions\Project;

use App\Models\Project;

class DeleteProject
{
    public function handle(Project $project): void
    {
        $project->delete();
    }
}
