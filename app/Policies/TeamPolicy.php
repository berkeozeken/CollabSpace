<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        // Admin takımlarla işlem yapamaz
        if ($user && $user->is_admin) {
            return false;
        }
        return null;
    }

    public function view(User $user, Team $team): bool
    {
        return $team->hasUser($user);
    }

    public function update(User $user, Team $team): bool
    {
        // Owner ya da Manager güncelleyebilir
        if ($team->isOwner($user)) return true;
        return $team->roleOf($user) === TeamRole::MANAGER;
    }

    public function delete(User $user, Team $team): bool
    {
        // Sadece Owner silebilir
        return $team->isOwner($user);
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        // Sadece Owner
        return $team->isOwner($user);
    }

    public function invite(User $user, Team $team): bool
    {
        // Owner ve Manager davet edebilir (yalnız arkadaşlardan kuralını UI/servis katmanında ayrıca doğrularız)
        if ($team->isOwner($user)) return true;
        return $team->roleOf($user) === TeamRole::MANAGER;
    }

    public function remove(User $user, Team $team): bool
    {
        // Owner & Manager üye çıkarabilir (Owner hariç)
        if ($team->isOwner($user)) return true;
        return $team->roleOf($user) === TeamRole::MANAGER;
    }

    public function changeRole(User $user, Team $team): bool
    {
        // Yalnızca Owner terfi/tenzil yapar (Manager rol atayamaz)
        return $team->isOwner($user);
    }
}
