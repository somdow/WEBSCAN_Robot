<?php

namespace App\Enums;

enum ModuleStatus: string
{
	case Ok = "ok";
	case Warning = "warning";
	case Bad = "bad";
	case Info = "info";

	public function label(): string
	{
		return match ($this) {
			self::Ok => "Pass",
			self::Warning => "Warning",
			self::Bad => "Fail",
			self::Info => "Info",
		};
	}

	public function color(): string
	{
		return match ($this) {
			self::Ok => "success",
			self::Warning => "warning",
			self::Bad => "danger",
			self::Info => "info",
		};
	}
}
