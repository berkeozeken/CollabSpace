<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
    ];

    protected $casts = [
        // none
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role', TeamRole::MEMBER->value);
    }

    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', TeamRole::MANAGER->value);
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function roleOf(User $user): ?TeamRole
    {
        $pivot = $this->users()->where('users.id', $user->id)->first()?->pivot;
        return $pivot ? TeamRole::from($pivot->role) : null;
    }
}
