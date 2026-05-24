<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = 'budgets';

    protected $fillable = [
        'user_id',
        'monthly_target',
        'weekly_target',
        'daily_target',
        'month_year',
        'alarm_config',
    ];

    protected $casts = [
        'alarm_config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
