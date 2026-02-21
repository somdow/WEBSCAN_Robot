<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
	use RefreshDatabase;
	public function test_terms_page_loads(): void
	{
		$this->get(route("legal.terms"))
			->assertOk()
			->assertSee("Terms of Service");
	}

	public function test_privacy_page_loads(): void
	{
		$this->get(route("legal.privacy"))
			->assertOk()
			->assertSee("Privacy Policy");
	}

	public function test_acceptable_use_page_loads(): void
	{
		$this->get(route("legal.acceptable-use"))
			->assertOk()
			->assertSee("Acceptable Use Policy");
	}
}
