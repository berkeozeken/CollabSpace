<?php

use App\Models\Team;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('team.{teamId}', function ($user, $teamId) {
    $team = Team::find($teamId);
    if (!$team) return false;

    return $team->users()->whereKey($user->id)->exists();
});
