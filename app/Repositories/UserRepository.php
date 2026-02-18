<?php

namespace App\Repositories;

use App\Interfaces\UserInterface;
use App\Models\User;

class UserRepository implements UserInterface
{
    public function create(array $data) : User
    {
        return User::create($data);
    }

    public function firstOrCreate(array $findBy, array $data) : User
    {
        return User::firstOrCreate($findBy, $data);
    }
 
    public function findByEmail(string $email) : ?User
    {
        return User::firstWhere('email', $email);
    }

    public function findByGoogleId(string $google_id) : ?User
    {
        return User::firstWhere('google_id', $google_id);
    }

    public function findByEmailWithTrashed(string $email) : ?User
    {
        return User::withTrashed()->firstWhere('email', $email);
    }
    
    public function existsBySlug(string $slug) : bool
    {
        return User::where('slug', $slug)->exists();
    }
}
