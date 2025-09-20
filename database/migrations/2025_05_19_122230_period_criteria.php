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
        Schema::create('period_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('ponderation')->nullable();
            $table->foreignId('period_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('criteria_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['period_id', 'criteria_id']);
            $table->timestamps();
        });
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
