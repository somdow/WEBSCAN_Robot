<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
	/**
	 * Determine whether the user can access the project.
	 * Checks that the user belongs to the project's organization.
	 */
	public function access(User $user, Project $project): bool
	{
		return $user->belongsToOrganization($project->organization_id);
	}
}
