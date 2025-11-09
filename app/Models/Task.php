<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'status',       // todo | in_progress | done
        'assignee_id',  // users.id nullable
        'creator_id',   // users.id
        'position',
        'due_at',
        'edited_at',
        'updated_by',
    ];

    protected $casts = [
        'due_at'     => 'datetime',
        'edited_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function team()     { return $this->belongsTo(Team::class); }
    public function creator()  { return $this->belongsTo(User::class, 'creator_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }
}
