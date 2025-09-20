<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    protected $fillable = ['name', 'description', 'status'];
    protected $table = 'criterias';

    public function periods()
    {
        return $this->belongsToMany(Period::class, 'period_criteria');
    }
}
