<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\User;

class UserAttachedToRepo implements Rule
{
    private $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function passes($attribute, $value)
    {
        return User::where('users.id', $this->userId)
            ->whereHas('repo', function ($query) use ($value) {
                $query->where('repos.id', $value);
            })->exists();
    }

    public function message()
    {
        return 'The user is not attached to the selected repository.';
    }
}
