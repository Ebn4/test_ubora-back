<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCandidatsHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candidats_history', function (Blueprint $table) {
            $table->id(); // id int NOT NULL PRIMARY KEY
            $table->decimal('post_work_id', 38, 0)->nullable();
            $table->decimal('form_id', 38, 0);
            $table->dateTime('form_submited_at');
            $table->string('etn_nom', 100);
            $table->string('etn_email', 100)->nullable();
            $table->string('etn_prenom', 100);
            $table->string('etn_postnom', 100);
            $table->date('etn_naissance')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('telephone', 100)->nullable();
            $table->string('adresse', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('nationalite', 100)->nullable();
            $table->string('cv', 100)->nullable();
            $table->string('releve_note_derniere_annee', 300)->nullable();
            $table->string('en_soumettant', 300)->nullable();
            $table->string('section_option', 300)->nullable();
            $table->string('j_atteste', 300)->nullable();
            $table->string('degre_parente_agent_orange', 300)->nullable();
            $table->string('annee_diplome_detat', 300)->nullable();
            $table->string('diplome_detat', 300)->nullable();
            $table->string('autres_diplomes_atttestation', 300)->nullable();
            $table->string('universite_institut_sup', 300)->nullable();
            $table->string('pourcentage_obtenu', 17)->nullable();
            $table->text('lettre_motivation')->nullable();
            $table->string('adresse_universite', 300)->nullable();
            $table->string('parente_agent_orange', 300)->nullable();
            $table->string('institution_scolaire', 300)->nullable();
            $table->string('montant_frais', 300)->nullable();
            $table->string('sexe', 300)->nullable();
            $table->string('attestation_de_reussite_derniere_annee', 300)->nullable();
            $table->string('user_last_login', 300)->nullable();
            $table->string('faculte', 300)->nullable();
            $table->decimal('evaluateur1', 38, 0)->nullable();
            $table->decimal('evaluateur2', 38, 0)->nullable();
            $table->decimal('evaluateur3', 38, 0)->nullable();
            $table->decimal('somme_notes', 38, 0)->nullable();
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
        Schema::dropIfExists('candidats_history');
    }
}
