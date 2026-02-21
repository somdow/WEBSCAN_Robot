<?php

namespace App\Enums;

enum DiscountType: string
{
	case Percent = "percent";
	case Fixed = "fixed";
	case FreeMonths = "free_months";

	public function label(): string
	{
		return match ($this) {
			self::Percent => "Percentage Off",
			self::Fixed => "Fixed Amount Off",
			self::FreeMonths => "Free Months",
		};
	}
}
