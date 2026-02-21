<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
	public function termsOfService(): View
	{
		return view("legal.terms");
	}

	public function privacyPolicy(): View
	{
		return view("legal.privacy");
	}

	public function acceptableUse(): View
	{
		return view("legal.acceptable-use");
	}
}
