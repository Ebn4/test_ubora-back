<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreselectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preselections', function (Blueprint $table) {
            $table->id();
            $table->decimal('candidature', 38, 0)->nullable();
            $table->boolean('critere_nationalite')->nullable();
            $table->boolean('critere_age')->nullable();
            $table->boolean('critere_annee_diplome_detat')->nullable();
            $table->boolean('critere_pourcentage')->nullable();
            $table->boolean('critere_cursus_choisi')->nullable();
            $table->boolean('critere_universite_institution_choisie')->nullable();
            $table->string('critere_cycle_etude', 30)->nullable();
            $table->boolean('pres_validation')->nullable();
            $table->text('pres_commentaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preselections');
    }
}
