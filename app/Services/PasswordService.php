<?php

namespace App\Services;

use App\Jobs\SendForgotPasswordMailJob;
use App\Models\User;
use App\Repositories\PasswordRepository;

class PasswordService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private PasswordRepository $password_repository
    ) {}

    public function forgotPassword(User $user) : void
    {
        //Delete others validation codes
        $this->password_repository->deleteByUserId($user->id);

        //Generate new validation Code & save
        $code = random_int(100000, 999999);
        $forgotPassword = $this->password_repository->create([
            'code' => $code,
            'user_id' => $user->id
        ]);

        //Add expiration time of code
        $forgotPassword->update(['expire_at' => $forgotPassword->created_at->addDays(2)]);

        // Send mail with code
        SendForgotPasswordMailJob::dispatch($user->email, $code);
    }
}
