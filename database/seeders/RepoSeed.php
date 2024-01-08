<?php

namespace Database\Seeders;

use App\Models\Repo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RepoSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();


        foreach ($users as $user) {
            $repos = Repo::factory()->count(100)->create();
            $user->repo()->attach($repos);
        }
    }
}
