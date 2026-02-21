<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TriggerScanRequest extends FormRequest
{
	/**
	 * Authorize: verify the user can access the project via policy.
	 */
	public function authorize(): bool
	{
		return Gate::allows("access", $this->route("project"));
	}

	public function rules(): array
	{
		return array();
	}
}
