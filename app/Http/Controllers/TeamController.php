<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->is_admin) {
            abort(403, 'Admins cannot access team pages.');
        }

        $teams = $user->teams()
            ->where('teams.owner_id', '!=', $user->id)
            ->with(['owner:id,name,email'])
            ->withCount('users')
            ->orderBy('teams.created_at', 'desc')
            ->get()
            ->map(function (Team $team) {
                $pivotRole = $team->pivot?->role;
                $roleStr = is_string($pivotRole) ? strtolower($pivotRole) : null;

                return [
                    'id'            => $team->id,
                    'name'          => $team->name,
                    'owner'         => $team->owner ? [
                        'id'    => $team->owner->id,
                        'name'  => $team->owner->name,
                        'email' => $team->owner->email,
                    ] : null,
                    'members_count' => $team->users_count,
                    'your_role'     => $roleStr,
                    'created_at'    => $team->created_at?->toIso8601String(),
                ];
            });

        $owned = $user->ownedTeams()
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Team $team) => [
                'id'            => $team->id,
                'name'          => $team->name,
                'members_count' => $team->users_count,
                'created_at'    => $team->created_at?->toIso8601String(),
            ]);

        return inertia('Teams/Index', [
            'teams'      => $teams,
            'ownedTeams' => $owned,
        ]);
    }

    public function show(Request $request, Team $team)
    {
        $this->authorize('view', $team);

        $authUser = $request->user();

        $members = $team->users()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('role')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'role'  => strtolower($u->pivot->role),
                'is_owner' => $team->owner_id === $u->id,
            ]);

        $can = [
            'invite'     => $authUser->can('invite', $team),
            'remove'     => $authUser->can('remove', $team),
            'changeRole' => $authUser->can('changeRole', $team),
            'update'     => $authUser->can('update', $team),
            'delete'     => $authUser->can('delete', $team),
            'transfer'   => $authUser->can('transferOwnership', $team),
        ];

        $yourRole = $team->roleOf($authUser);
        $yourRoleStr = $yourRole ? strtolower($yourRole->value) : null;

        return inertia('Teams/Show', [
            'team' => [
                'id'    => $team->id,
                'name'  => $team->name,
                'owner' => [
                    'id'    => $team->owner->id,
                    'name'  => $team->owner->name,
                    'email' => $team->owner->email,
                ],
            ],
            'members'  => $members,
            'can'      => $can,
            'yourRole' => $yourRoleStr,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->is_admin) {
            abort(403, 'Admins cannot create teams.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($data, $user) {
            $team = Team::create([
                'name'     => $data['name'],
                'owner_id' => $user->id,
            ]);

            $team->users()->attach($user->id, ['role' => TeamRole::OWNER->value]);

            return redirect()->route('teams.index')->with('success', 'Team created.');
        });
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $team->update(['name' => $data['name']]);

        return back()->with('success', 'Team updated.');
    }

    public function destroy(Request $request, Team $team)
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    public function transferOwnership(Request $request, Team $team)
    {
        $this->authorize('transferOwnership', $team);

        $data = $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ]);

        $newOwnerId = (int) $data['new_owner_id'];

        if (!$team->users()->where('users.id', $newOwnerId)->exists()) {
            return back()->withErrors(['new_owner_id' => 'User must be a member of the team.']);
        }

        return DB::transaction(function () use ($team, $newOwnerId) {
            $team->users()->updateExistingPivot($team->owner_id, ['role' => TeamRole::MANAGER->value]);
            $team->users()->updateExistingPivot($newOwnerId, ['role' => TeamRole::OWNER->value]);
            $team->update(['owner_id' => $newOwnerId]);

            return back()->with('success', 'Ownership transferred.');
        });
    }
}
