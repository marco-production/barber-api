<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\AuthException;
//use App\Models\UserEntities\ForgotPassword;
//use App\Models\UserEntities\EmailVerification;
//use App\Http\Controllers\Api\GeolocationController;
use App\Http\Controllers\Controller;
//use App\Mail\ForgotPasswordMail;
//use App\Mail\VerifyAccountMail;
//use App\Traits\PhoneVerification;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Services\PasswordService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Carbon\Carbon;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Support\Facades\Mail;
//use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;


class AuthController extends Controller
{    
    //use PhoneVerification;

    public function __construct(
        private AuthService $auth_service,
        private UserService $user_service,
        private PasswordService $password_service
    ) 
    {}
  
    /**
     * Login User
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request) 
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:190',
                'password' => 'required|string|min:6',
            ]);

            if($validator->fails()) 
                return response()->json(['errors' => $validator->errors()->all()], 400);

            $result = $this->auth_service->login($validator->validated());

            return response()->json($result, 200);

        } catch (AuthException $e) {
            return response()->json(['errors' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * Register User
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(RegisterRequest $request) 
    {
        try {
            $data = $request->safe()->merge([
                'avatar' => 'default.png',
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(10),
            ]);
            
            $result = $this->auth_service->register($data);

            return response()->json(['user' => $result], 201);

        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }

    /**
     * Forgot Password
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190'
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        try {
            //Verify if exists this User
            $user = $this->user_service->findByEmailWithTrashed($request->email);

            // Forgot password process
            $this->password_service->forgotPassword($user);
            return response()->json(['message' => 'Password recovery email sent successfully.'], 200);

        } catch(\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }


}
