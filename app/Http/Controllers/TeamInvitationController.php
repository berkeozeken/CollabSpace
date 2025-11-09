<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    // Kullanıcının kendi davetlerini listele (pending)
    public function index(Request $request)
    {
        $user = $request->user();

        $invitations = TeamInvitation::with(['team:id,name', 'inviter:id,name,email'])
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Invitations/Index', [
            'invitations' => $invitations,
        ]);
    }

    // Davet oluştur (owner/manager)
    public function store(Request $request, Team $team)
    {
        $this->authorize('invite', $team);

        $data = $request->validate([
            'email' => ['required','email','max:255'],
        ]);

        $existing = TeamInvitation::where('team_id', $team->id)
            ->where('email', $data['email'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => true,
                'invitation_id' => $existing->id,
                'status' => 'pending',
            ]);
        }

        $inv = TeamInvitation::create([
            'team_id'    => $team->id,
            'inviter_id' => $request->user()->id,
            'email'      => $data['email'],
            'token'      => Str::random(40),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'ok' => true,
            'invitation_id' => $inv->id,
            'status' => 'pending',
        ], 201);
    }

    // Daveti kabul et
    public function accept(Request $request, TeamInvitation $invitation)
    {
        $user = $request->user();

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Invitation not pending'], 422);
        }
        if ($invitation->expires_at && now()->greaterThan($invitation->expires_at)) {
            return response()->json(['message' => 'Invitation expired'], 410);
        }
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return response()->json(['message' => 'Email mismatch'], 403);
        }

        $isMember = $invitation->team->users()->where('users.id', $user->id)->exists();
        if (!$isMember) {
            $invitation->team->users()->attach($user->id, ['role' => 'member']);
        }

        $invitation->update(['status' => 'accepted']);

        return response()->json(['ok' => true]);
    }

    // Daveti reddet
    public function decline(Request $request, TeamInvitation $invitation)
    {
        $user = $request->user();

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return response()->json(['message' => 'Email mismatch'], 403);
        }
        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Invitation not pending'], 422);
        }

        $invitation->update(['status' => 'declined']);

        return response()->json(['ok' => true]);
    }

    // (opsiyonel) daveti iptal et (inviter/owner)
    public function cancel(Request $request, TeamInvitation $invitation)
    {
        $team = $invitation->team;
        $this->authorize('invite', $team);

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Invitation not pending'], 422);
        }

        $invitation->update(['status' => 'declined']);

        return response()->json(['ok' => true]);
    }
}
