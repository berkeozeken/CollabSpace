<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Events\MessageDeleted;
use App\Events\MessageRead as MessageReadEvent;
use App\Events\MessageUpdated;
use App\Events\TypingStarted;
use App\Events\TypingStopped;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function chat(Team $team)
    {
        $this->authorizeTeamMember($team);

        return Inertia::render('Teams/Chat', [
            'team' => [
                'id'   => $team->id,
                'name' => $team->name ?? ('Team #'.$team->id),
            ],
        ]);
    }

    public function index(Request $request, Team $team)
    {
        $this->authorizeTeamMember($team);

        $perPage  = (int) $request->integer('per_page', 30);
        $perPage  = max(10, min($perPage, 100));
        $beforeId = (int) $request->integer('before_id', 0);
        $meId     = (int) $request->user()->id;

        $base = Message::with(['user:id,name'])
            ->where('team_id', $team->id);

        if ($beforeId > 0) {
            $base->where('id', '<', $beforeId);
        }

        $chunk = (clone $base)
            ->orderBy('id', 'desc')
            ->limit($perPage)
            ->get();

        $items = $chunk->reverse()->values();
        $ids   = $items->pluck('id')->all();

        $reactions = $this->reactionsForMessages($ids, $meId);

        // append reactions
        foreach ($items as $m) {
            $m->reactions = $reactions[$m->id] ?? [];
        }

        $nextBeforeId = $items->first()->id ?? null;

        $hasMore = false;
        if ($nextBeforeId) {
            $hasMore = Message::where('team_id', $team->id)
                ->where('id', '<', $nextBeforeId)
                ->exists();
        }

        return response()->json([
            'data'            => $items,
            'has_more'        => $hasMore,
            'next_before_id'  => $nextBeforeId,
        ]);
    }

    public function store(Request $request, Team $team)
    {
        $this->authorizeTeamMember($team);

        $key = "msg:{$team->id}:".$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many messages, slow down.'], 429);
        }
        RateLimiter::hit($key, 1);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        $message->load('user:id,name');
        $message->reactions = []; // yeni mesajda boş

        MessageCreated::dispatch($message);

        return response()->json([
            'ok'      => true,
            'message' => $message,
        ], 201);
    }

    public function update(Request $request, Team $team, Message $message)
    {
        $this->authorizeTeamMember($team);

        if ($message->team_id !== $team->id || $message->user_id !== $request->user()->id) {
            abort(403, 'Not allowed.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $validated['body']]);
        $message->refresh();

        MessageUpdated::dispatch($message);

        return response()->json(['ok' => true, 'message' => $message]);
    }

    public function destroy(Request $request, Team $team, Message $message)
    {
        $this->authorizeTeamMember($team);

        if ($message->team_id !== $team->id || $message->user_id !== $request->user()->id) {
            abort(403, 'Not allowed.');
        }

        $message->delete();
        MessageDeleted::dispatch($message);

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, Team $team)
    {
        $this->authorizeTeamMember($team);

        $data = $request->validate([
            'message_ids'   => ['required','array','min:1'],
            'message_ids.*' => ['integer','distinct'],
        ]);

        $userId = $request->user()->id;
        $now    = now();

        $ids = array_values(array_unique(array_map('intval', $data['message_ids'])));

        $rows = [];
        foreach ($ids as $mid) {
            $rows[] = ['message_id' => $mid, 'user_id' => $userId, 'read_at' => $now];
        }

        MessageRead::upsert($rows, ['message_id','user_id'], ['read_at']);

        $lastId = max($ids);
        if ($mr = MessageRead::where('message_id', $lastId)->where('user_id', $userId)->first()) {
            MessageReadEvent::dispatch($mr, $team->id);
        }

        return response()->json(['ok' => true]);
    }

    public function typing(Request $request, Team $team)
    {
        $this->authorizeTeamMember($team);

        $validated = $request->validate([
            'state' => ['required','in:start,stop'],
        ]);

        $u = $request->user();

        if ($validated['state'] === 'start') {
            TypingStarted::dispatch($team->id, $u->id, $u->name);
        } else {
            TypingStopped::dispatch($team->id, $u->id);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeTeamMember(Team $team): void
    {
        $userId = Auth::id();
        if (!$userId) abort(401, 'Not authenticated.');

        $isMember = $team->users()->where('users.id', $userId)->exists();
        if (!$isMember) abort(403, 'You are not a member of this team.');
    }

    /**
     * @return array<int, array<int,array{emoji:string,count:int,me:bool,users:array}>>
     */
    private function reactionsForMessages(array $messageIds, int $meId): array
    {
        if (empty($messageIds)) return [];

        $rows = DB::table('message_reactions as mr')
            ->join('users as u', 'u.id', '=', 'mr.user_id')
            ->whereIn('mr.message_id', $messageIds)
            ->select('mr.message_id','mr.emoji','mr.user_id','u.name')
            ->orderBy('mr.message_id')
            ->orderBy('mr.emoji')
            ->orderBy('u.name')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $mid = (int) $r->message_id;
            $out[$mid] = $out[$mid] ?? [];
            $key = null;
            // aynı message+emoji için slot bul
            foreach ($out[$mid] as $i => $slot) {
                if ($slot['emoji'] === $r->emoji) { $key = $i; break; }
            }
            if ($key === null) {
                $out[$mid][] = ['emoji' => $r->emoji, 'count' => 0, 'me' => false, 'users' => []];
                $key = array_key_last($out[$mid]);
            }
            $out[$mid][$key]['count']++;
            $out[$mid][$key]['me'] = $out[$mid][$key]['me'] || ((int)$r->user_id === $meId);
            $out[$mid][$key]['users'][] = ((int)$r->user_id === $meId) ? 'You' : $r->name;
        }

        return $out;
    }
}
