<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Repo;
use App\Models\User;

class RepoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Repo $repo)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Repo $repo)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Repo $repo): bool
    {
        return $user->repo()->where('repo_id', $repo->id)->wherePivot('is_admin', true)->exists();
    }

}
