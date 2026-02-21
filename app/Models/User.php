<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
	use HasApiTokens, HasFactory, Notifiable;

	/**
	 * Guard against duplicate verification emails.
	 * Laravel 12 registers the SendEmailVerificationNotification listener
	 * multiple times due to auto-discovery + explicit registration + configureEmailVerification().
	 */
	private bool $verificationEmailSent = false;

	public function sendEmailVerificationNotification(): void
	{
		if ($this->verificationEmailSent) {
			return;
		}

		$this->verificationEmailSent = true;

		parent::sendEmailVerificationNotification();
	}

	protected $fillable = array(
		"name",
		"email",
		"phone",
		"password",
		"ai_provider",
		"ai_gemini_key",
		"ai_openai_key",
		"ai_anthropic_key",
	);

	protected $hidden = array(
		"password",
		"remember_token",
		"ai_gemini_key",
		"ai_openai_key",
		"ai_anthropic_key",
	);

	protected function casts(): array
	{
		return array(
			"email_verified_at" => "datetime",
			"deactivated_at" => "datetime",
			"password" => "hashed",
			"is_super_admin" => "boolean",
			"ai_gemini_key" => "encrypted",
			"ai_openai_key" => "encrypted",
			"ai_anthropic_key" => "encrypted",
		);
	}

	/* ── Relationships ── */

	public function organizations(): BelongsToMany
	{
		return $this->belongsToMany(Organization::class)
			->withPivot("role")
			->withTimestamps();
	}

	public function scansTriggered(): HasMany
	{
		return $this->hasMany(Scan::class, "triggered_by");
	}

	public function auditLogs(): HasMany
	{
		return $this->hasMany(AuditLog::class);
	}

	/* ── Helpers ── */

	/**
	 * Get the user's role within a specific organization
	 */
	public function roleInOrganization(Organization $organization): ?OrganizationRole
	{
		$pivot = $this->organizations()
			->where("organization_id", $organization->id)
			->first()?->pivot;

		if (!$pivot) {
			return null;
		}

		return OrganizationRole::from($pivot->role);
	}

	/** Per-request cache for currentOrganization() to avoid redundant queries. */
	private ?Organization $cachedCurrentOrganization = null;
	private bool $currentOrganizationResolved = false;

	/**
	 * The user's current/default organization.
	 *
	 * Priority: session-selected org > owned org > first joined org.
	 * The session value is set by the org switcher (OrganizationController@switch).
	 * Result is cached for the lifetime of this model instance.
	 */
	public function currentOrganization(): ?Organization
	{
		if ($this->currentOrganizationResolved) {
			return $this->cachedCurrentOrganization;
		}

		$this->currentOrganizationResolved = true;
		$this->cachedCurrentOrganization = $this->resolveCurrentOrganization();

		return $this->cachedCurrentOrganization;
	}

	/**
	 * Clear the in-memory organization cache so the next call to
	 * currentOrganization() re-resolves from the database.
	 */
	public function resetOrganizationCache(): void
	{
		$this->currentOrganizationResolved = false;
		$this->cachedCurrentOrganization = null;
		$this->unsetRelation("organizations");
	}

	private function resolveCurrentOrganization(): ?Organization
	{
		$sessionOrgId = session("current_organization_id");

		if ($sessionOrgId !== null) {
			$sessionOrg = $this->organizations()->where("organizations.id", $sessionOrgId)->first();
			if ($sessionOrg !== null) {
				return $sessionOrg;
			}
		}

		$organizations = $this->organizations()->get();

		if ($organizations->isEmpty()) {
			return null;
		}

		if ($organizations->count() === 1) {
			return $organizations->first();
		}

		return $organizations->firstWhere("pivot.role", OrganizationRole::Owner->value)
			?? $organizations->first();
	}

	public function isSuperAdmin(): bool
	{
		return $this->is_super_admin === true;
	}

	public function isActive(): bool
	{
		return $this->deactivated_at === null;
	}

	public function deactivate(): void
	{
		$this->deactivated_at = now();
		$this->save();
	}

	public function reactivate(): void
	{
		$this->deactivated_at = null;
		$this->save();
	}

	/**
	 * Gate access to the Filament admin panel — super admins only.
	 */
	public function canAccessPanel(Panel $panel): bool
	{
		return $this->isSuperAdmin();
	}

	/**
	 * Whether this user is a member of the given organization.
	 */
	public function belongsToOrganization(int $organizationId): bool
	{
		return $this->organizations()
			->where("organizations.id", $organizationId)
			->exists();
	}

	/**
	 * Whether this user is the owner of their current organization.
	 */
	public function isOrganizationOwner(): bool
	{
		$organization = $this->currentOrganization();

		return $organization !== null && $this->id === $organization->owner()?->id;
	}

	/**
	 * Whether this user's current organization plan includes AI access.
	 */
	public function canAccessAi(): bool
	{
		$organization = $this->currentOrganization();

		return $organization !== null && $organization->canAccessAi();
	}
}
