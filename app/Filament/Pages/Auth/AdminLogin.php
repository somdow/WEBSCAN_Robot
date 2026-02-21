<?php

namespace App\Filament\Pages\Auth;

use App\Services\Auth\AdminLoginVerificationService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

/**
 * Custom Filament login page that intercepts admin authentication
 * and requires email verification before granting access.
 */
class AdminLogin extends Login
{
	public function authenticate(): ?LoginResponse
	{
		$data = $this->form->getState();

		/** @var \Illuminate\Auth\SessionGuard $authGuard */
		$authGuard = Filament::auth();

		$authProvider = $authGuard->getProvider();
		$credentials = $this->getCredentialsFromFormData($data);

		$user = $authProvider->retrieveByCredentials($credentials);

		if ((!$user) || (!$authProvider->validateCredentials($user, $credentials))) {
			$this->fireFailedEvent($authGuard, $user, $credentials);
			$this->throwFailureValidationException();
		}

		if (!$user->isSuperAdmin()) {
			return parent::authenticate();
		}

		if (!$user->isActive()) {
			$this->throwFailureValidationException();
		}

		$verificationService = app(AdminLoginVerificationService::class);
		$verificationLink = $verificationService->generateVerificationLink($user, "admin");
		$verificationService->sendVerificationEmail($user, $verificationLink);

		Notification::make()
			->title("Verification email sent")
			->body("Check your inbox for a sign-in link. It expires in 15 minutes.")
			->info()
			->send();

		return null;
	}
}
