<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("waitlist_signups", function (Blueprint $table) {
			$table->id();
			$table->string("email")->index();
			$table->string("desired_url", 2048)->nullable();
			$table->string("ip_address", 45)->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("waitlist_signups");
	}
};
