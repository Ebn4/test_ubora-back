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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preselections', function (Blueprint $table) {
            //
        });
    }
};
