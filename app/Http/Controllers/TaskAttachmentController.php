<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    // POST /teams/{team}/tasks/{task}/attachments
    public function store(Request $request, Team $team, Task $task)
    {
        $this->authorize('update', $task);
        if ($task->team_id !== $team->id) abort(404);

        $data = $request->validate([
            'file' => ['required','file','max:20480'], // 20MB
        ]);

        $file   = $data['file'];
        $path   = $file->store("tasks/{$task->id}", 'public');

        $att = TaskAttachment::create([
            'task_id'       => $task->id,
            'user_id'       => $request->user()->id,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ]);

        // url accessor dahil
        return response()->json(['ok' => true, 'attachment' => $att->fresh()], 201);
    }

    // DELETE /attachments/{attachment}
    public function destroy(TaskAttachment $attachment)
    {
        $task = $attachment->task;
        $this->authorize('update', $task);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }

    // GET /attachments/{attachment}/download
    public function download(TaskAttachment $attachment)
    {
        $task = $attachment->task;
        $this->authorize('view', $task); // taskı gören herkes indirebilsin

        $fullPath = Storage::disk('public')->path($attachment->path);
        if (!is_file($fullPath)) abort(404);

        return response()->download(
            $fullPath,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime ?: 'application/octet-stream']
        );
    }
}
