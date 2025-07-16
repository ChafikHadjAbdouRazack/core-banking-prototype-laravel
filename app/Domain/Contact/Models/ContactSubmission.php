<?php

namespace App\Domain\Contact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder whereNull(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder whereNotNull(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder whereDate(string $column, mixed $operator, string|\DateTimeInterface|null $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder whereMonth(string $column, mixed $operator, string|\DateTimeInterface|null $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder whereYear(string $column, mixed $value)
 * @method static \Illuminate\Database\Eloquent\Builder orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder latest(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder oldest(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder with(array|string $relations)
 * @method static \Illuminate\Database\Eloquent\Builder distinct(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder groupBy(string ...$groups)
 * @method static \Illuminate\Database\Eloquent\Builder having(string $column, string $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder selectRaw(string $expression, array $bindings = [])
 * @method static \Illuminate\Database\Eloquent\Collection get(array $columns = ['*'])
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static|null first(array $columns = ['*'])
 * @method static static firstOrFail(array $columns = ['*'])
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static static firstOrNew(array $attributes, array $values = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static static create(array $attributes = [])
 * @method static int count(string $columns = '*')
 * @method static mixed sum(string $column)
 * @method static mixed avg(string $column)
 * @method static mixed max(string $column)
 * @method static mixed min(string $column)
 * @method static bool exists()
 * @method static bool doesntExist()
 * @method static \Illuminate\Support\Collection pluck(string $column, string|null $key = null)
 * @method static bool delete()
 * @method static bool update(array $values)
 * @method static \Illuminate\Database\Eloquent\Builder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder query()
 */
class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'priority',
        'attachment_path',
        'ip_address',
        'user_agent',
        'status',
        'responded_at',
        'response_notes',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Get subject label.
     */
    public function getSubjectLabelAttribute()
    {
        $labels = [
            'account'    => 'Account Issues',
            'technical'  => 'Technical Support',
            'billing'    => 'Billing & Payments',
            'gcu'        => 'GCU Questions',
            'api'        => 'API & Integration',
            'compliance' => 'Compliance & Security',
            'other'      => 'Other',
        ];

        return $labels[$this->subject] ?? 'Unknown';
    }

    /**
     * Get priority color.
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            'low'    => 'gray',
            'medium' => 'yellow',
            'high'   => 'orange',
            'urgent' => 'red',
        ];

        return $colors[$this->priority] ?? 'gray';
    }

    /**
     * Mark as responded.
     */
    public function markAsResponded($notes = null)
    {
        $this->update(
            [
            'status'         => 'responded',
            'responded_at'   => now(),
            'response_notes' => $notes,
            ]
        );
    }

    /**
     * Get the activity logs for this model.
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function logs()
    {
        return $this->morphMany(\App\Domain\Activity\Models\Activity::class, 'subject');
    }
}
