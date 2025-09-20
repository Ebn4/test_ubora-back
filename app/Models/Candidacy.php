<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Candidacy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    protected $fillable = [
        'post_work_id',
        'form_id',
        'form_submited_at',
        'etn_nom',
        'etn_email',
        'etn_prenom',
        'etn_postnom',
        'ville',
        'telephone',
        'adresse',
        'province',
        'nationalite',
        'cv',
        'releve_note_derniere_annee',
        'en_soumettant',
        'section_option',
        'j_atteste',
        'degre_parente_agent_orange',
        'annee_diplome_detat',
        'diplome_detat',
        'autres_diplomes_atttestation',
        'universite_institut_sup',
        'pourcentage_obtenu',
        'lettre_motivation',
        'adresse_universite',
        'parente_agent_orange',
        'institution_scolaire',
        'faculte',
        'montant_frais',
        'sexe',
        'attestation_de_reussite_derniere_annee',
        'user_last_login',
        'evaluateur1',
        'evaluateur2',
        'evaluateur3',
        'period_id',
        'is_allowed',
    ];

    protected $attribute = ['moyenne', 'evaluations_effectuées'];

    protected $table = 'candidats';

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function dispatch(): BelongsToMany
    {
        return $this->belongsToMany(Evaluator::class, 'dispatch_preselections')->withPivot('id');
    }

    public function interview(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Interview::class);
    }

    public function dispatchPreselections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DispatchPreselection::class);
    }
}
