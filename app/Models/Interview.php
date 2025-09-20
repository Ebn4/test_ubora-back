<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $fillable = [
        'candidacy_id',
    ];

    public function candidacy()
    {
        return $this->belongsTo(Candidacy::class);
    }

    public function selectionResults(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Criteria::class, 'selection_result')
            ->withPivot(['result', 'evaluator_id']);
    }

}
