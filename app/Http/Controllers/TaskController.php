<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    // Inertia board sayfası
    public function page(Request $request, Team $team)
    {
        $this->authorize('viewAny', [Task::class, $team]);

        $members = $team->users()->select('users.id','users.name','users.email')->get();

        return Inertia::render('Teams/Tasks', [
            'team'    => $team->only(['id','name']),
            'members' => $members,
        ]);
    }

    // JSON list
    public function index(Request $request, Team $team)
    {
        $this->authorize('viewAny', [Task::class, $team]);

        $items = Task::with([
                'creator:id,name',
                'assignee:id,name,email',
                'attachments:id,task_id,original_name,size,mime,path,created_at' // url accessor gelecek
            ])
            ->where('team_id', $team->id)
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    // JSON create
    public function store(Request $request, Team $team)
    {
        $this->authorize('create', [Task::class, $team]);

        $data = $request->validate([
            'title'       => ['required','string','max:200'],
            'description' => ['nullable','string','max:5000'],
            'status'      => ['nullable','in:todo,in_progress,done'],
            'assignee_id' => ['nullable','integer'],
            'position'    => ['nullable','integer','min:0'],
            'due_at'      => ['nullable','date'],
        ]);

        if (!empty($data['assignee_id'])) {
            $isMember = $team->users()->where('users.id', $data['assignee_id'])->exists();
            if (!$isMember) return response()->json(['message' => 'Assignee must be a team member'], 422);
        }

        $task = Task::create([
            'team_id'     => $team->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'todo',
            'assignee_id' => $data['assignee_id'] ?? null,
            'position'    => $data['position'] ?? 0,
            'due_at'      => $data['due_at'] ?? null,
            'creator_id'  => $request->user()->id,
        ])->load('creator:id,name','assignee:id,name,email');

        return response()->json(['ok' => true, 'task' => $task], 201);
    }

    // JSON update
    public function update(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);
        if ($task->team_id !== $team->id) abort(404);

        $data = $request->validate([
            'title'       => ['sometimes','required','string','max:200'],
            'description' => ['sometimes','nullable','string','max:5000'],
            'status'      => ['sometimes','required','in:todo,in_progress,done'],
            'assignee_id' => ['sometimes','nullable','integer'],
            'position'    => ['sometimes','nullable','integer','min:0'],
            'due_at'      => ['sometimes','nullable','date'],
        ]);

        if (array_key_exists('assignee_id', $data) && !empty($data['assignee_id'])) {
            $isMember = $team->users()->where('users.id', $data['assignee_id'])->exists();
            if (!$isMember) return response()->json(['message' => 'Assignee must be a team member'], 422);
        }

        $task->fill($data);
        $task->edited_at = now();
        $task->updated_by = $request->user()->id;
        $task->save();

        $task->load('creator:id,name','assignee:id,name,email');

        return response()->json(['ok' => true, 'task' => $task]);
    }

    // JSON delete
    public function destroy(Request $request, Team $team, Task $task)
    {
        $this->authorize('delete', $task);
        if ($task->team_id !== $team->id) abort(404);

        $task->delete();
        return response()->json(['ok' => true]);
    }
}
