<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Mesajı düzenleme yetkisi:
     * - Mesaj sahibi
     * - Takım Owner/Manager
     */
    public function update(User $user, Message $message): bool
    {
        if ($message->user_id === $user->id) {
            return true;
        }

        return $this->isTeamOwnerOrManager($user, $message->team_id);
    }

    /**
     * Mesajı soft delete:
     * - Mesaj sahibi
     * - Takım Owner/Manager
     */
    public function delete(User $user, Message $message): bool
    {
        if ($message->user_id === $user->id) {
            return true;
        }

        return $this->isTeamOwnerOrManager($user, $message->team_id);
    }

    /**
     * Silinen mesajı geri getirme:
     * - Sadece Owner/Manager
     */
    public function restore(User $user, Message $message): bool
    {
        return $this->isTeamOwnerOrManager($user, $message->team_id);
    }

    private function isTeamOwnerOrManager(User $user, int $teamId): bool
    {
        // team_user pivot: role = owner|manager|member
        $row = $user->teams()
            ->where('teams.id', $teamId)
            ->first()?->pivot;

        if (!$row) {
            return false;
        }

        $role = strtolower((string) $row->role);
        return in_array($role, ['owner', 'manager'], true);
    }
}
