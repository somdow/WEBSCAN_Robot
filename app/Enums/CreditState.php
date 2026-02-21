<?php

namespace App\Enums;

enum CreditState: string
{
	case Unclaimed = "unclaimed";
	case Claimed = "claimed";
	case Released = "released";
}
