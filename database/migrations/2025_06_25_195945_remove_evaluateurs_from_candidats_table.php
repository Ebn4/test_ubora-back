<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidats', function (Blueprint $table) {
            $table->dropColumn(['evaluateur1', 'evaluateur2', 'evaluateur3', 'somme_notes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidats', function (Blueprint $table) {
            $table->decimal('evaluateur1', 38, 0)->nullable();
            $table->decimal('evaluateur2', 38, 0)->nullable();
            $table->decimal('evaluateur3', 38, 0)->nullable();
            $table->decimal('somme_notes', 38, 0)->nullable();
        });
    }
};
