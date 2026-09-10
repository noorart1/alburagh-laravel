<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionCode extends Model
{
    protected $fillable = [
        'drupal_nid',
        'used_at',
        'code',
        'serial',
        'amount',
        'status',
        'code_series_id',
    ];
    protected $casts = [
        'subscription_date' => 'datetime',
        'expires_at' => 'datetime',
    ];
    public function series()
    {
        return $this->belongsTo(CodeSeries::class, 'code_series_id');
    }

    public function userSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'subscription_code_id');
    }
}