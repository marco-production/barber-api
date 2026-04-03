<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Jobs\SendForgotPasswordMailJob;
use App\Models\User;
use App\Repositories\PasswordRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class PasswordService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly PasswordRepository $password_repository,
        private readonly UserRepository $user_repository
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

    public function validateCode(string $email, int $code) : void
    {
        $user = $this->user_repository->findByEmailWithTrashed($email);
        $forgotPassword = $this->password_repository->codeValidation($user->id, $code);

        if(!$forgotPassword)
            throw new \Exception("The code you entered does not match your code. Retry.", 400); // Joel

        if(now()->toDateTimeString() > $forgotPassword->expire_at)
            throw new \Exception("The code has expired, request a new code.", 400); // Joel

        $forgotPassword->update(['is_verified' => true]);
    }

    public function restorePassword(string $email, string $password) : array
    {
        $user = $this->user_repository->findByEmailWithTrashed($email);
        $forgotPasswordVerified = $this->password_repository->isVerified($user->id);

        if (!$forgotPasswordVerified)
            throw new \Exception("There isn't password reset request.", 400); // Joel

        // Update password
        $user->update(['password' => Hash::make($password)]);

        // Delete record
        $forgotPasswordVerified->delete();

        // If the user isn't verified don't return token
        if(!$user->is_verified || $user->deleted_at != null)
            return [
                'user' => new UserResource($user)
            ];

        //Create token
        $accessToken = $user->createToken('mobile');

        // Save user agent
        $accessToken->accessToken->forceFill([
            //'user_agent' => $request->user_agent,
            //'ip' => $request->ip
        ])->save();

        return [
            'user' => new UserResource($user), 
            'accessToken' => $accessToken
        ];
    }
}
