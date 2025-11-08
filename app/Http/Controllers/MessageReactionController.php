<?php

namespace App\Http\Controllers;

use App\Events\ReactionToggled;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageReactionController extends Controller
{
    public function toggle(Request $request, Team $team, Message $message)
    {
        $this->authorizeMember($team);
        if ($message->team_id !== $team->id) abort(404);

        $data = $request->validate([
            'emoji' => ['required','string','max:8'],
        ]);

        $userId = (int) $request->user()->id;

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $userId)
            ->where('emoji', $data['emoji'])
            ->first();

        $direction = 'add';
        if ($existing) {
            $existing->delete();
            $direction = 'remove';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => $userId,
                'emoji'      => $data['emoji'],
            ]);
        }

        // Güncel durum: count + isimler + id'ler (ISMİ "You" yapmıyoruz)
        $rows = DB::table('message_reactions as mr')
            ->join('users as u', 'u.id', '=', 'mr.user_id')
            ->where('mr.message_id', $message->id)
            ->where('mr.emoji', $data['emoji'])
            ->select('mr.user_id', 'u.name')
            ->orderBy('u.name')
            ->get();

        $count   = $rows->count();
        $names   = [];
        $userIds = [];
        foreach ($rows as $r) {
            $userIds[] = (int) $r->user_id;
            $names[]   = $r->name;
        }

        // broadcast (POZİSYONEL argüman!)
        ReactionToggled::dispatch(
            $team->id,
            $message->id,
            $userId,
            $data['emoji'],
            $direction,
            $count,
            $names,     // plain names
            $userIds    // ids
        );

        return response()->json([
            'ok'       => true,
            'emoji'    => $data['emoji'],
            'count'    => $count,
            'me'       => in_array($userId, $userIds, true),
            'users'    => $names,     // plain names
            'user_ids' => $userIds,   // ids
        ]);
    }

    private function authorizeMember(Team $team): void
    {
        $userId = Auth::id();
        if (!$userId) abort(401);

        $isMember = $team->users()->where('users.id', $userId)->exists();
        if (!$isMember) abort(403, 'You are not a member of this team.');
    }
}
