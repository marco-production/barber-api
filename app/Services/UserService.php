<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    private readonly UserRepository $userRepository;

    /**
     * Create a new class instance.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function findByEmailWithTrashed(string $email) : User
    {
        $user = $this->userRepository->findByEmailWithTrashed($email);

        if(!$user) {
            throw new \Exception("The email you entered does not exist in our records.", 400); // Joel
        }

        return $user;
    }
}
