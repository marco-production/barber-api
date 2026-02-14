<?php

namespace App\Interfaces;

use App\Models\User;

interface UserInterface
{
    public function create(array $data) : User;

    //public function update(array $data) : User;

    public function findByEmail(string $email) : User;
    
    public function existsBySlug(string $slug) : bool;
}
