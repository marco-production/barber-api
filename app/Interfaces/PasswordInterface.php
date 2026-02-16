<?php

namespace App\Interfaces;

use App\Models\UserEntities\ForgotPassword;

interface PasswordInterface
{
    public function create(array $data) : ForgotPassword;

    public function deleteByUserId(int $userId) : void;

    public function codeValidation(int $userId, int $code) : ForgotPassword;

    public function isVerified(int $userId) : ForgotPassword;
}
