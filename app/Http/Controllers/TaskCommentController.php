<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCommentController extends Controller
{
    public function store(Request $request, Team $team, Task $task)
    {
        if ($task->team_id !== $team->id) abort(404);

        $userId = Auth::id();
        $isMember = $team->users()->where('users.id', $userId)->exists();
        if (!$isMember) abort(403, 'Not a team member.');

        $data = $request->validate([
            'body' => ['required','string','max:4000'],
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'body'    => $data['body'],
        ]);

        $comment->load('user:id,name');

        return response()->json(['ok' => true, 'comment' => $comment], 201);
    }
}
