<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageReactionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');

    // ==== Teams ====
    Route::get('/teams',                  [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams',                 [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}',           [TeamController::class, 'show'])->name('teams.show');
    Route::patch('/teams/{team}',         [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}',        [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/transfer', [TeamController::class, 'transferOwnership'])->name('teams.transfer');

    // ==== Team Members ====
    Route::post('/teams/{team}/members',              [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::delete('/teams/{team}/members/{user}',     [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
    Route::patch('/teams/{team}/members/{user}/role', [TeamMemberController::class, 'updateRole'])->name('teams.members.role');

    // ==== Invitations ====
    Route::post('/teams/{team}/invitations',          [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
    Route::post('/invitations/{invitation}/accept',   [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/decline',  [TeamInvitationController::class, 'decline'])->name('invitations.decline');
    Route::post('/invitations/{invitation}/cancel',   [TeamInvitationController::class, 'cancel'])->name('invitations.cancel');

    // ==== Team Chat ====
    Route::get('/teams/{team}/chat',                  [MessageController::class, 'chat'])->name('teams.chat');
    Route::get('/teams/{team}/messages',              [MessageController::class, 'index'])->name('teams.messages.index');
    Route::post('/teams/{team}/messages',             [MessageController::class, 'store'])->name('teams.messages.store');
    Route::patch('/teams/{team}/messages/{message}',  [MessageController::class, 'update'])->name('teams.messages.update');
    Route::delete('/teams/{team}/messages/{message}', [MessageController::class, 'destroy'])->name('teams.messages.destroy');
    Route::post('/teams/{team}/messages/mark-read',   [MessageController::class, 'markRead'])->name('teams.messages.markRead');
    Route::post('/teams/{team}/typing',               [MessageController::class, 'typing'])->name('teams.typing');

    // ==== Reactions ====
    Route::post('/teams/{team}/messages/{message}/reactions/toggle',
        [MessageReactionController::class, 'toggle'])->name('teams.messages.reactions.toggle');

    // ==== Team Tasks (JSON) ====
    Route::get   ('/teams/{team}/tasks',           [TaskController::class, 'index'])->name('teams.tasks.index');
    Route::post  ('/teams/{team}/tasks',           [TaskController::class, 'store'])->name('teams.tasks.store');
    Route::patch ('/teams/{team}/tasks/{task}',    [TaskController::class, 'update'])->name('teams.tasks.update');
    Route::delete('/teams/{team}/tasks/{task}',    [TaskController::class, 'destroy'])->name('teams.tasks.destroy');

    // ==== Task Attachments ====
    Route::post  ('/teams/{team}/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('teams.tasks.attachments.store');
    Route::delete('/attachments/{attachment}',              [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get   ('/attachments/{attachment}/download',     [TaskAttachmentController::class, 'download'])->name('attachments.download');

    // ==== Team Tasks (BOARD PAGE) ====
    Route::get('/teams/{team}/tasks/board', [TaskController::class, 'page'])->name('teams.tasks.board');

    // ==== Task Comments ====
    Route::post('/teams/{team}/tasks/{task}/comments', [TaskCommentController::class, 'store'])
        ->name('teams.tasks.comments.store');
});

// ==== Profile ====
Route::middleware('auth')->group(function () {
    Route::get   ('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch ('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',  [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==== Invitations list for current user ====
Route::get('/invitations', [TeamInvitationController::class, 'index'])->name('invitations.index');

require __DIR__ . '/auth.php';
