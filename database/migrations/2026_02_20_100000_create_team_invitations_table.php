<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("team_invitations", function (Blueprint $table) {
			$table->id();
			$table->foreignId("organization_id")->constrained()->cascadeOnDelete();
			$table->foreignId("invited_by")->constrained("users")->cascadeOnDelete();
			$table->string("email");
			$table->string("token", 64)->unique();
			$table->timestamp("expires_at");
			$table->timestamp("accepted_at")->nullable();
			$table->timestamps();

			$table->index(array("organization_id", "email"));
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("team_invitations");
	}
};
