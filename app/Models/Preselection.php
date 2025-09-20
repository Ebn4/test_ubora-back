<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preselection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'period_criteria_id',
        'dispatch_preselections_id',
        'valeurvaleur'
    ];

    protected $table = 'preselections';

    public function periodCriteria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PeriodCriteria::class);
    }

    public function dispatchPreselection(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DispatchPreselection::class);
    }
}
