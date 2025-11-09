<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class TaskPolicy
{
    protected function isTeamMember(User $user, Team $team): bool
    {
        return DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    protected function isManagerOrOwner(User $user, Team $team): bool
    {
        $row = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->select('role')
            ->first();

        if (!$row) return false;
        $role = strtoupper($row->role ?? '');
        return in_array($role, ['OWNER', 'MANAGER'], true);
    }

    public function viewAny(User $user, Team $team): Response
    {
        return $this->isTeamMember($user, $team)
            ? Response::allow()
            : Response::deny('Not a team member.');
    }

    public function view(User $user, Task $task): Response
    {
        return $this->isTeamMember($user, $task->team)
            ? Response::allow()
            : Response::deny('Not a team member.');
    }

    public function create(User $user, Team $team): Response
    {
        return $this->isTeamMember($user, $team)
            ? Response::allow()
            : Response::deny('Not a team member.');
    }

    public function update(User $user, Task $task): Response
    {
        if (!$this->isTeamMember($user, $task->team)) {
            return Response::deny('Not a team member.');
        }
        return ($task->creator_id === $user->id || $this->isManagerOrOwner($user, $task->team))
            ? Response::allow()
            : Response::deny('Not allowed.');
    }

    public function delete(User $user, Task $task): Response
    {
        if (!$this->isTeamMember($user, $task->team)) {
            return Response::deny('Not a team member.');
        }
        return ($task->creator_id === $user->id || $this->isManagerOrOwner($user, $task->team))
            ? Response::allow()
            : Response::deny('Not allowed.');
    }
}
