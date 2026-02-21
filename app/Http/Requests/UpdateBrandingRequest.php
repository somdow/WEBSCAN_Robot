<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()->currentOrganization()?->canWhiteLabel() ?? false;
	}

	public function rules(): array
	{
		return array(
			"pdf_company_name" => array("nullable", "string", "max:100"),
			"brand_color" => array("nullable", "string", "regex:/^#[0-9A-Fa-f]{6}$/"),
			"logo" => array("nullable", "image", "max:512", "mimes:jpg,jpeg,png"),
		);
	}

	public function messages(): array
	{
		return array(
			"brand_color.regex" => "Brand color must be a valid hex color (e.g. #4F46E5).",
			"logo.max" => "Logo file must be under 512 KB.",
		);
	}
}
