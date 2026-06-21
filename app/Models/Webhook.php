<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user's outbound webhook endpoint. One per user; ownership is enforced by
 * always accessing it through the User::webhook() relationship.
 *
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property bool $is_enabled
 */
class Webhook extends Model
{
    protected $fillable = [
        'url',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delivery attempts for this webhook, most recent first.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->latest();
    }
}
