<?php

namespace App\Http\Requests;

use App\Rules\SafeExternalUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation rules for project create and update forms.
 */
abstract class ProjectFormRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return array(
			"name" => array("required", "string", "max:255"),
			"url" => array("required", "url:http,https", "max:2048", new SafeExternalUrl()),
			"target_keywords" => array("nullable", "string", "max:1000"),
		);
	}

	public function messages(): array
	{
		return array(
			"url.url" => "Please enter a valid URL starting with http:// or https://",
		);
	}
}
