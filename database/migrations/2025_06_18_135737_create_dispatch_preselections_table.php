<?php

use App\Models\Candidacy;
use App\Models\Evaluator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispatch_preselections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Candidacy::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Evaluator::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_preselection');
    }
};
