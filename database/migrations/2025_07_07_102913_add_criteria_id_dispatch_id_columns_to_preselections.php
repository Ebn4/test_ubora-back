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
        Schema::table('preselections', function (Blueprint $table) {
            $table->foreignId('period_criteria_id')
                ->references('id')
                ->on('period_criteria');
            $table->foreignId('dispatch_preselections_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('valeur')->nullable();
            $table->unique(['period_criteria_id', 'dispatch_preselections_id'],'pres_period_dispatch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preselections', function (Blueprint $table) {
            $table->dropColumn('candidature');
            $table->dropColumn('critere_nationalite');
            $table->dropColumn('critere_age');
            $table->dropColumn('critere_annee_diplome_detat');
            $table->dropColumn('critere_pourcentage');
            $table->dropColumn('critere_cursus_choisi');
            $table->dropColumn('critere_universite_institution_choisie');
            $table->dropColumn('critere_cycle_etude');
            $table->dropColumn('pres_validation');
            $table->dropColumn('pres_commentaire');
        });
    }
};
