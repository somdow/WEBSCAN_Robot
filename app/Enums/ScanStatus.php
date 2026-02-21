<?php

namespace App\Enums;

enum ScanStatus: string
{
	case Pending = "pending";
	case Running = "running";
	case Completed = "completed";
	case Failed = "failed";
	case Blocked = "blocked";

	public function label(): string
	{
		return match ($this) {
			self::Pending => "Pending",
			self::Running => "Running",
			self::Completed => "Completed",
			self::Failed => "Failed",
			self::Blocked => "Blocked",
		};
	}

	public function color(): string
	{
		return match ($this) {
			self::Pending => "warning",
			self::Running => "info",
			self::Completed => "success",
			self::Failed => "danger",
			self::Blocked => "warning",
		};
	}

	public function isTerminal(): bool
	{
		return in_array($this, [self::Completed, self::Failed, self::Blocked]);
	}
}
