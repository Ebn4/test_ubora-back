<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'year',
        'status'
    ];

    public function criteria()
    {
        return $this->belongsToMany(Criteria::class, 'period_criteria')
            ->withPivot(['type', 'ponderation']);
    }

    public function evaluators(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Evaluator::class);
    }

    public function candidats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Candidacy::class);
    }
}
