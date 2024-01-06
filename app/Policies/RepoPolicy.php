<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Repo;
use App\Models\User;

class RepoPolicy
{

    public function has(User $user, Repo $repo)
    {
        return User::where('users.id', $user->id)
            ->whereHas('repo', function ($query) use ($repo) {
                $query->where('repos.id', $repo->id);
            })->exists();
    }
    public function is_admin(User $user, Repo $repo): bool
    {
        return $user->repo()->where('repo_id', $repo->id)->wherePivot('is_admin', true)->exists();
    }
}
