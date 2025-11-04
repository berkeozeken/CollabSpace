<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function store(Request $request, Team $team)
    {
        $this->authorize('invite', $team);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $userToAdd = User::where('email', $data['email'])->first();

        if (! $userToAdd) {
            return $this->jsonOrBack($request, ['email' => 'User not found.'], 422);
        }

        if ($team->hasUser($userToAdd)) {
            return $this->jsonOrBack($request, ['email' => 'User is already a team member.'], 422);
        }

        $team->users()->attach($userToAdd->id, ['role' => TeamRole::MEMBER->value]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'   => true,
                'user' => [
                    'id'       => $userToAdd->id,
                    'name'     => $userToAdd->name,
                    'email'    => $userToAdd->email,
                    'role'     => TeamRole::MEMBER->value,
                    'is_owner' => false,
                ],
            ], 200);
        }

        return back()->with('success', 'Member invited (added).');
    }

    public function destroy(Request $request, Team $team, User $user)
    {
        $this->authorize('remove', $team);

        if ($team->owner_id === $user->id) {
            return $this->jsonOrBack($request, ['member' => 'Owner cannot be removed.'], 422);
        }

        if (! $team->hasUser($user)) {
            return $this->jsonOrBack($request, ['member' => 'User is not a team member.'], 422);
        }

        $team->users()->detach($user->id);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true], 200);
        }

        return back()->with('success', 'Member removed.');
    }

    // Rol değiştirme (OWNER hariç)
    public function updateRole(Request $request, Team $team, User $user)
    {
        $this->authorize('changeRole', $team);

        // JSON cevap
        $request->headers->set('Accept', 'application/json');

        // Gelen role'u case-insensitive al, KÜÇÜK HARF'e normalize et
        $roleRaw   = (string) $request->input('role', '');
        $roleLower = strtolower($roleRaw);

        // Sadece manager/member'a izin
        if (! in_array($roleLower, [TeamRole::MANAGER->value, TeamRole::MEMBER->value], true)) {
            return response()->json(['ok' => false, 'error' => 'Invalid role value.'], 422);
        }

        if (! $team->hasUser($user)) {
            return response()->json(['ok' => false, 'error' => 'User is not a team member.'], 422);
        }

        if ($team->owner_id === $user->id) {
            return response()->json(['ok' => false, 'error' => 'Use ownership transfer to change owner.'], 422);
        }

        $team->users()->updateExistingPivot($user->id, ['role' => $roleLower]);

        return response()->json(['ok' => true, 'role' => $roleLower], 200);
    }

    private function jsonOrBack(Request $request, array $errors, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'errors' => $errors], $status);
        }
        return back()->withErrors($errors);
    }
}
