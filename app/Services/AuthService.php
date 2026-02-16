<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
       private UserRepository $userRepository
    ) {}

    public function login(array $data, ?string $user_agent, ?string $ip): array
    {
        $auth = $this->userRepository->findByEmailWithTrashed($data['email']);

        if (!$auth) {
            throw new AuthException(__('user_does_not_exist'), 404);
        }

        if (!Hash::check($data['password'], $auth->password)) {
            throw new AuthException(__('password_mismatch'), 404);
        }

        if (!$auth->active) {
            throw new AuthException(__('disabled_user'), 422);
        }

        if ($auth->deleted_at != null) {
            throw new AuthException(__('user_account_deactivated'), 403);
        }

        if (!$auth->hasAnyRole(['User', 'Admin', 'Super Admin'])) {
            throw new AuthException(__('not_have_permission'), 401);
        }

        // If user is not verified return it without token
        if (!$auth->is_verified) {
            return [
                'user' => $auth,
                'accessToken' => null
            ];
        }

        //Create token
        $accessToken = $auth->createToken('mobile');

        // Save user agent
        $accessToken->accessToken->forceFill([
            'user_agent' => $user_agent,
            'ip' => $ip
        ])->save();

        return [
            'user' => $auth,
            'accessToken' => $accessToken->plainTextToken
        ];
    }

    public function register(array $data) : User
    {
        //Create User
        $user = $this->userRepository->create($data);

        //Create slug
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower(str_replace(' ', '', $data['name']).'.'.str_replace(' ', '', $data['lastname'])));
        if($this->userRepository->existsBySlug($slug)) $slug = $slug.'.'.$user->id;
        $user->update(['slug' => $slug]);

        //Assign role
        $user->assignRole('User');

        return $user;
    }

}
