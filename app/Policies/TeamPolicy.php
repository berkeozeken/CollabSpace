<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->hasUser($user) || $user->is_admin;
    }

    public function update(User $user, Team $team): bool
    {
        // Owner or Manager can update team
        if ($user->is_admin) return true;
        $role = $team->roleOf($user);
        return $user->isOwnerOf($team) || $role === TeamRole::MANAGER;
    }

    public function invite(User $user, Team $team): bool
    {
        // Owner or Manager can invite
        return $this->update($user, $team);
    }

    public function remove(User $user, Team $team, User $target): bool
    {
        // Manager can remove members (not owner). Owner can remove anyone but not themself (without ownership transfer).
        if ($user->is_admin) return true;

        if ($target->isOwnerOf($team)) {
            return false;
        }
        $role = $team->roleOf($user);
        return $user->isOwnerOf($team) || $role === TeamRole::MANAGER;
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        // Only current owner
        return $user->isOwnerOf($team) || $user->is_admin;
    }

    public function delete(User $user, Team $team): bool
    {
        // Only owner (or platform admin)
        return $user->isOwnerOf($team) || $user->is_admin;
    }
}
