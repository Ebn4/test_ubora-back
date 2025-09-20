<?php

use App\Models\Candidacy;
use App\Models\Criteria;
use App\Models\Evaluator;
use App\Models\Interview;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('selection_result', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Interview::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Evaluator::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Criteria::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('result');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selection_result');
    }
};
