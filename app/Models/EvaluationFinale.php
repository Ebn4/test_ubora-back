<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationFinale extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluateur',
        'candidature',
        'critere_doss_academique',
        'critere_lettre_motivation',
        'critere_communication_skills',
        'critere_cv',
        'commentaire',
        'total',
       
    ];

    protected $table = 'evaluationsfinales';
}
