<?php

use App\Enums\PeriodStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->enum("status", array_column(PeriodStatusEnum::cases(), 'value'))
                ->default(PeriodStatusEnum::STATUS_DISPATCH->value)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
