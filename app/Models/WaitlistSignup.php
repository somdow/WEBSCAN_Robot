<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistSignup extends Model
{
	protected $fillable = array(
		"email",
		"desired_url",
		"ip_address",
	);
}
