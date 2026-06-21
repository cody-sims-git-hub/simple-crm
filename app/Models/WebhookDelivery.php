<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single webhook delivery attempt — the log row written each time a payload
 * is sent (currently manual "test" sends).
 *
 * @property int $id
 * @property int $webhook_id
 * @property string $event
 * @property int|null $status_code
 * @property bool $successful
 * @property string|null $error
 */
class WebhookDelivery extends Model
{
    protected $fillable = [
        'event',
        'status_code',
        'successful',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
