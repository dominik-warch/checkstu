<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional identity color, picked by an admin, used to tint a member's
        // task cards. Nullable — task cards fall back to the default look until
        // a color is set.
        Schema::table('users', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
