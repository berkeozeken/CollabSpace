<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teamRole(Team $team): ?TeamRole
    {
        $pivot = $this->teams()->where('team_id', $team->id)->first()?->pivot;
        return $pivot ? TeamRole::from($pivot->role) : null;
    }

    public function isOwnerOf(Team $team): bool
    {
        return $team->owner_id === $this->id;
    }

    public function isManagerOf(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::MANAGER;
    }

    public function isMemberOf(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::MEMBER;
    }
}
