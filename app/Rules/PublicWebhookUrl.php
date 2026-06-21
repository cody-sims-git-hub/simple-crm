<?php

namespace App\Rules;

use App\Support\OutboundUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects webhook URLs that would let the server reach private, loopback, or
 * reserved addresses (SSRF). Wraps OutboundUrl so save-time validation and the
 * pre-send runtime check share one implementation.
 */
class PublicWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! OutboundUrl::isPublic($value)) {
            $fail('The webhook URL must be a public http(s) address. Private and reserved addresses are not allowed.');
        }
    }
}
