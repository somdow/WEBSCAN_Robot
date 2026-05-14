<?php

namespace App\Filament\Resources\WaitlistSignups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WaitlistSignupForm
{
	/**
	 * Edit form for waitlist signups. Email stays editable so an admin can
	 * correct a visitor-submitted typo before re-engaging them. The desired
	 * URL and IP are forensic data — locked read-only so the historical
	 * record cannot be tampered with through the admin UI.
	 */
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components(array(
				TextInput::make("email")
					->label("Email address")
					->email()
					->required(),
				TextInput::make("desired_url")
					->label("Wanted to scan")
					->url()
					->readOnly(),
				TextInput::make("ip_address")
					->label("IP address")
					->readOnly(),
			));
	}
}
