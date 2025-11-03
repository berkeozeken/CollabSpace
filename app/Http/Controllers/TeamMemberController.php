<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function store(Request $request, Team $team)
    {
        $this->authorize('invite', $team);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'in:' . implode(',', TeamRole::values())],
        ]);

        $role = $data['role'] ?? TeamRole::MEMBER->value;

        if ($team->users()->where('users.id', $data['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'User already in team.']);
        }

        $team->users()->attach($data['user_id'], ['role' => $role]);

        return back()->with('success', 'Member added.');
    }

    public function update(Request $request, Team $team, User $user)
    {
        $this->authorize('update', $team);

        $data = $request->validate([
            'role' => ['required', 'in:' . implode(',', TeamRole::values())],
        ]);

        // Prevent setting owner via this endpoint
        if ($data['role'] === TeamRole::OWNER->value) {
            return back()->withErrors(['role' => 'Use ownership transfer endpoint.']);
        }

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['user' => 'Cannot change role of current owner.']);
        }

        if (! $team->users()->where('users.id', $user->id)->exists()) {
            return back()->withErrors(['user' => 'User not in team.']);
        }

        $team->users()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return back()->with('success', 'Member role updated.');
    }

    public function destroy(Request $request, Team $team, User $user)
    {
        $this->authorize('remove', [$team, $user]);

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['user' => 'Owner cannot be removed. Transfer ownership first.']);
        }

        $team->users()->detach($user->id);

        return back()->with('success', 'Member removed.');
    }
}
