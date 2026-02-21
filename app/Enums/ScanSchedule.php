<?php

namespace App\Enums;

enum ScanSchedule: string
{
	case Weekly = "weekly";
	case Monthly = "monthly";

	public function label(): string
	{
		return match ($this) {
			self::Weekly => "Weekly",
			self::Monthly => "Monthly",
		};
	}
}
