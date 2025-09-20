<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsfinalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluationsfinales', function (Blueprint $table) {
            $table->decimal('id', 38, 0)->primary(); // ici, id est decimal et non auto-incrémenté
            $table->decimal('evaluateur', 38, 0);
            $table->decimal('candidature', 38, 0);
            $table->decimal('critere_doss_academique', 38, 0);
            $table->decimal('critere_lettre_motivation', 38, 0);
            $table->decimal('critere_communication_skills', 38, 0);
            $table->decimal('total', 38, 0);
            $table->string('critere_cv', 100);
            $table->text('commentaire')->nullable();
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
        Schema::dropIfExists('evaluationsfinales');
    }
}
