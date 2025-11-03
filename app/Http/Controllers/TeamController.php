<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = $request->user()->teams()->with('owner')->get();
        $owned = $request->user()->ownedTeams()->get();

        return inertia('Teams/Index', [
            'teams' => $teams,
            'ownedTeams' => $owned,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $team = Team::create([
                'name' => $data['name'],
                'owner_id' => $request->user()->id,
            ]);

            // Owner as pivot with owner role
            $team->users()->attach($request->user()->id, ['role' => TeamRole::OWNER->value]);

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

        if (! $team->users()->where('users.id', $newOwnerId)->exists()) {
            return back()->withErrors(['new_owner_id' => 'User must be a member of the team.']);
        }

        return DB::transaction(function () use ($team, $newOwnerId) {
            // demote old owner to manager
            $team->users()->updateExistingPivot($team->owner_id, ['role' => TeamRole::MANAGER->value]);

            // promote new owner
            $team->users()->updateExistingPivot($newOwnerId, ['role' => TeamRole::OWNER->value]);

            // set owner on team record
            $team->update(['owner_id' => $newOwnerId]);

            return back()->with('success', 'Ownership transferred.');
        });
    }
}
