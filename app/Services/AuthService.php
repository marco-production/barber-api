<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\UserRepository;
//use App\Traits\Handlesvalidations;
use Google\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Resources\UserResource;

class AuthService
{
    //use Handlesvalidations;

    /**
     * Create a new class instance.
     */
    public function __construct(
       private UserRepository $userRepository
    ) {}

    public function login(array $data, ?string $user_agent, ?string $ip): array
    {
        $user = $this->userRepository->findByEmailWithTrashed($data['email']);

        if (!$user) {
            throw new AuthException(__('user_does_not_exist'), 404);
        }

        if ($user->password == null) {
            throw new AuthException(/* __('password_mismatch') */'This account uses Google login. Please use Google or reset your password.', 409);
            // Joel - agregar el error a en.json y es.json
        }

        if (!Hash::check($data['password'], $user->password)) {
            throw new AuthException(__('password_mismatch'), 404);
        }

        if (!$user->active) {
            throw new AuthException(__('disabled_user'), 422);
        }

        if ($user->deleted_at != null) {
            throw new AuthException(__('user_account_deactivated'), 403);
        }

        if (!$user->hasAnyRole(['User'])) {
            throw new AuthException(__('not_have_permission'), 401);
        }

        // If user is not verified return it without token
        if (!$user->is_verified) {
            return [
                'user' => $user,
                'accessToken' => null
            ];
        }

        //Create token
        $accessToken = $user->createToken('mobile');

        // Save user agent
        $accessToken->accessToken->forceFill([
            'user_agent' => $user_agent,
            'ip' => $ip
        ])->save();

        return [
            'user' => $user,
            'accessToken' => $accessToken->plainTextToken
        ];
    }

    public function register(array $data) : User
    {
        //Create User
        $user = $this->userRepository->create($data);

        //Create slug
        $slug = Str::slug($data['name']. ' ' .$data['lastname']);
        if($this->userRepository->existsBySlug($slug)) $slug = $slug.'.'.$user->id;
        $user->update(['slug' => $slug]);

        //Assign role
        $user->assignRole('User');

        return $user;
    }

    public function loginWithGoogle(array $data, ?string $user_agent, ?string $ip) : array
    {
        $client = new Client([
            'client_id' => env('GOOGLE_CLIENT_ID')
        ]);

        $payload = $client->verifyIdToken($data['id_token']);

        if(!$payload)
            throw new AuthException("Invalid token", 401); // Joel - Cambiar este mensaje en el archivo en.json

        if (!$payload['email_verified'])
            throw new AuthException("Google email not verified", 401); // Joel - Cambiar este mensaje en el archivo en.json


        $user = $this->userRepository->findByGoogleId($payload['sub']);

        if (!$user) {

                $user = $this->userRepository->findByEmail($payload['email']);

                if ($user) {

                    $user->update([
                        'google_id' => $payload['sub'],
                        'is_verified' => true,
                        'email_verified_at' => now()
                    ]);

                } else {

                    $user = $this->userRepository->create([
                        'email' => $payload['email'],
                        'google_id' => $payload['sub'],
                        'name' => $payload['given_name'] ?? 'User',
                        'lastname' => $payload['family_name'] ?? null,
                        'is_verified' => true,
                        'slug' => Str::slug($payload['name'] . ' ' . ($payload['family_name'] ?? '')),
                        'email_verified_at' => now(),
                        'avatar' => $payload['picture'] ?? null
                    ]);

                    $user->assignRole('User');

                }
        }

        // Validations
        if (!$user->active) {
            throw new AuthException(__('disabled_user'), 422);
        }

        if ($user->deleted_at != null) {
            throw new AuthException(__('user_account_deactivated'), 403);
        }

        if (!$user->hasAnyRole(['User'])) {
            throw new AuthException(__('not_have_permission'), 401);
        }

        // Create token
        $accessToken = $user->createToken('mobile');

        // Save user agent
        $accessToken->accessToken->forceFill([
            'user_agent' => $user_agent,
            'ip' => $ip
        ])->save();

        return [
            'user' => new UserResource($user),
            'accessToken' => $accessToken->plainTextToken
        ];
    }

}
