<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AddPageRequest extends FormRequest
{
	public function authorize(): bool
	{
		$project = $this->route("project");

		return Gate::allows("access", $project);
	}

	public function rules(): array
	{
		return array(
			"url" => array("required", "url:http,https", "max:2048"),
		);
	}

	public function messages(): array
	{
		return array(
			"url.required" => "Please enter a URL to analyze.",
			"url.url" => "Please enter a valid URL (starting with http:// or https://).",
			"url.max" => "The URL is too long (max 2048 characters).",
		);
	}
}
