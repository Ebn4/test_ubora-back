<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistorique extends Model
{
    protected $fillable = [
        'period_id',
        'user_id',
        'status',
    ];
}
