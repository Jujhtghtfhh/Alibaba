<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember who created each item and review, so only the owner (or an admin) can change it.
     * Nullable, because rows created before this migration have no owner.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
