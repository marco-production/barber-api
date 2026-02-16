<?php

namespace App\Repositories;

use App\Interfaces\PasswordInterface;
use App\Models\UserEntities\ForgotPassword;

class PasswordRepository implements PasswordInterface
{
    public function create(array $data) : ForgotPassword
    {
        return ForgotPassword::create(['code' => $data['code'], 'user_id' => $data['user_id']]);
    }

    public function deleteByUserId(int $userId) : void
    {
        ForgotPassword::where('user_id', $userId)->delete();
    }

    public function codeValidation(int $userId, int $code) : ForgotPassword
    {
        return ForgotPassword::where('user_id', $userId)->where('code', $code)->first();
    }

    public function isVerified(int $userId) : ForgotPassword
    {
        return ForgotPassword::where('user_id', $userId)->verified()->first();
    }
}
