<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectionResult extends Model
{
    use HasFactory;

    protected $table = 'selection_result';

    public function criteria()
    {
        return $this->belongsTo(Criteria::class, 'criteria_id');
    }

    public function interview()
    {
        return $this->belongsTo(Interview::class, 'interview_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(Evaluator::class, 'evaluator_id');
    }

}
