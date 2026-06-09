<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Lead
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $insurance_type
 * @property int $lead_score
 * @property string $priority
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Lead query()
 */
class Lead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'insurance_type',
        'lead_score',
        'priority',
        'status',
        'notes',
    ];

    /**
     * Scope every query to the authenticated user's leads, and stamp new
     * leads with the current user. This enforces per-user data ownership
     * across all queries (and route-model binding) without controller changes.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (Auth::check()) {
                $query->where($query->getModel()->qualifyColumn('user_id'), Auth::id());
            }
        });

        static::creating(function (Lead $lead) {
            if (Auth::check() && empty($lead->user_id)) {
                $lead->user_id = Auth::id();
            }
        });
    }

    /**
     * The user that owns this lead.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
