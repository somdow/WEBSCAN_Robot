<?php

namespace App\Http\Requests;

use App\Enums\AiProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$providerValues = array_map(fn(AiProvider $provider) => $provider->value, AiProvider::cases());

		return array(
			"ai_provider" => array("nullable", Rule::in($providerValues)),
			"ai_gemini_key" => array("nullable", "string", "max:4096"),
			"ai_openai_key" => array("nullable", "string", "max:4096"),
			"ai_anthropic_key" => array("nullable", "string", "max:4096"),
			"clear_gemini_key" => array("nullable", "boolean"),
			"clear_openai_key" => array("nullable", "boolean"),
			"clear_anthropic_key" => array("nullable", "boolean"),
		);
	}
}
