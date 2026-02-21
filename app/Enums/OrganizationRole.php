<?php

namespace App\Enums;

enum OrganizationRole: string
{
	case Owner = "owner";
	case Admin = "admin";
	case Member = "member";
	case Viewer = "viewer";

	public function label(): string
	{
		return match ($this) {
			self::Owner => "Owner",
			self::Admin => "Admin",
			self::Member => "Member",
			self::Viewer => "Viewer",
		};
	}

	/**
	 * Whether this role can manage organization settings and billing
	 */
	public function canManageOrganization(): bool
	{
		return in_array($this, [self::Owner, self::Admin]);
	}

	/**
	 * Whether this role can create and manage projects
	 */
	public function canManageProjects(): bool
	{
		return in_array($this, [self::Owner, self::Admin, self::Member]);
	}

	/**
	 * Whether this role can trigger scans
	 */
	public function canTriggerScans(): bool
	{
		return in_array($this, [self::Owner, self::Admin, self::Member]);
	}
}
