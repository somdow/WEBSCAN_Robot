<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateProjectRequest extends ProjectFormRequest
{
	/**
	 * Verify the user belongs to the project's organization.
	 */
	public function authorize(): bool
	{
		$project = $this->route("project");

		return $project !== null && Gate::allows("access", $project);
	}
}
