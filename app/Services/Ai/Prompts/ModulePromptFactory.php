<?php

namespace App\Services\Ai\Prompts;

use App\Contracts\AiPromptInterface;
use App\Models\ScanModuleResult;
use App\Services\Ai\Prompts\Modules\ContentKeywordsPrompt;
use App\Services\Ai\Prompts\Modules\ContentReadabilityPrompt;
use App\Services\Ai\Prompts\Modules\H1TagPrompt;
use App\Services\Ai\Prompts\Modules\H2H6TagsPrompt;
use App\Services\Ai\Prompts\Modules\ImageAnalysisPrompt;
use App\Services\Ai\Prompts\Modules\LinkAnalysisPrompt;
use App\Services\Ai\Prompts\Modules\MetaDescriptionPrompt;
use App\Services\Ai\Prompts\Modules\SchemaOrgPrompt;
use App\Services\Ai\Prompts\Modules\SocialTagsPrompt;
use App\Services\Ai\Prompts\Modules\TitleTagPrompt;

class ModulePromptFactory
{
	/**
	 * Module keys that have specialized prompt classes.
	 * All other modules use the generic ModuleOptimizationPrompt.
	 */
	private const SPECIALIZED_PROMPTS = array(
		"titleTag" => TitleTagPrompt::class,
		"metaDescription" => MetaDescriptionPrompt::class,
		"h1Tag" => H1TagPrompt::class,
		"h2h6Tags" => H2H6TagsPrompt::class,
		"contentReadability" => ContentReadabilityPrompt::class,
		"contentKeywords" => ContentKeywordsPrompt::class,
		"socialTags" => SocialTagsPrompt::class,
		"imageAnalysis" => ImageAnalysisPrompt::class,
		"schemaOrg" => SchemaOrgPrompt::class,
		"linkAnalysis" => LinkAnalysisPrompt::class,
	);

	/**
	 * Modules where AI optimization provides unique, creative per-site value.
	 * Binary/factual modules (favicon, viewport, WordPress version checks, etc.)
	 * are excluded because the fix is always the same regardless of the site.
	 */
	private const AI_ELIGIBLE_MODULES = array(
		"titleTag",
		"metaDescription",
		"h1Tag",
		"h2h6Tags",
		"contentReadability",
		"contentKeywords",
		"socialTags",
		"imageAnalysis",
		"schemaOrg",
		"linkAnalysis",
	);

	/**
	 * Whether a module supports AI optimization.
	 */
	public static function isEligible(string $moduleKey): bool
	{
		return in_array($moduleKey, self::AI_ELIGIBLE_MODULES, true);
	}

	/**
	 * Resolve the appropriate prompt class for a module result.
	 * Specialized modules get dedicated prompts with tailored instructions;
	 * all others receive the generic professional assessment prompt.
	 */
	public function make(ScanModuleResult $moduleResult, string $siteUrl): AiPromptInterface
	{
		$promptClass = self::SPECIALIZED_PROMPTS[$moduleResult->module_key]
			?? ModuleOptimizationPrompt::class;

		return new $promptClass($moduleResult, $siteUrl);
	}
}
