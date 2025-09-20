<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchPreselection extends Model
{
    public function candidacy()
    {
        return $this->belongsTo(Candidacy::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(Evaluator::class);
    }

    public function preselections(){
        return $this->hasMany(Preselection::class, 'dispatch_preselections_id');
    }
}
