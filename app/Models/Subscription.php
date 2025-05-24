<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'user_id',
        'plan_id',
        'start_date',
        'end_date',
        'notes',
        'is_custom_schedule',
        'schedule_details',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_custom_schedule' => 'boolean',
        'schedule_details' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive()
    {
        return now()->between($this->start_date, $this->end_date);
    }
}
