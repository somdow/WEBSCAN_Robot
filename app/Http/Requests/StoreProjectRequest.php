<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreProjectRequest extends ProjectFormRequest
{
	public function rules(): array
	{
		$organizationId = $this->user()->currentOrganization()?->id;

		return array_merge(parent::rules(), array(
			"url" => array(
				"required",
				"url:http,https",
				"max:2048",
				Rule::unique("projects", "url")->where("organization_id", $organizationId),
			),
		));
	}

	public function messages(): array
	{
		return array_merge(parent::messages(), array(
			"url.unique" => "A project with this URL already exists in your organization.",
		));
	}
}
