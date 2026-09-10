<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $fillable = [
        'drupal_nid',
        'user_id',
        'subscription_code_id',
        'amount',
        'subscription_date',
        'expires_at',
        'type',
    ];

    protected $casts = [
        'subscription_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionCode()
    {
        return $this->belongsTo(
            SubscriptionCode::class,
            'subscription_code_id'
        );
    }
}