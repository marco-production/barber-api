<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private UserRepository $userRepository;

    /**
     * Create a new class instance.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(object $data)
    {
        $auth = $this->userRepository->findByEmailWithTrashed($data->email);

        // Change locale
        if($data->has('locale'))
            App::setLocale($data->locale);

        if($auth){
            // Validate password credentials
            if(Hash::check($data->password, $auth->password)){

                if(!$auth->active){
                    // If the user account is disabled
                    return response()->json(['message' => __('disabled_user')], 422);

                } else if($auth->deleted_at != null) {
                    // If the user account is deactivated
                    return response()->json(['message' => __('user_account_deactivated')], 403); 
                }

                // Verify if the user have the necessary roles to login
                if($auth->hasAnyRole(['User', 'Admin', 'Super Admin'])){
                    $auth['roles'] = $auth->getRoleNames();
                    $auth->makeHidden(['created_at', 'updated_at']);
                    
                    // If the user isn't verified don't return token
                    if(!$auth->is_verified)
                        return response()->json(['user' => $auth], 200);

                    // If everything is correct return user and JWT
                    $accessToken = $auth->createToken('Xperience Personal Access Client')->accessToken;
                    return response()->json(['user' => $auth, 'accessToken' => $accessToken], 200);
                }

                return response()->json(['errors' => __('not_have_permission')], 401);
            } 

            return response()->json(['errors' => __('password_mismatch')], 404);
        } 

        return response()->json(['errors' => __('user_does_not_exist')], 404);
    }

    

}
