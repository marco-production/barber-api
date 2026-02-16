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

    /* public function update(array $data) : User
    {
        
    } */
 
    public function findByEmail(string $email) : User
    {
        return User::firstWhere('email', $email);
    }

    public function findByEmailWithTrashed(string $email) : User
    {
        return User::withTrashed()->firstWhere('email', $email);
    }
    
    public function existsBySlug(string $slug) : bool
    {
        return User::where('slug', $slug)->exists();
    }
}
