<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\UserEntities\ForgotPassword;
use App\Models\UserEntities\EmailVerification;
use App\Http\Controllers\Api\GeolocationController;
use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifyAccountMail;
use App\Traits\PhoneVerification;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;


class AuthController extends Controller
{    
    use PhoneVerification;
    private $locales = ['en', 'es'];
  
    /**
     * Login User
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'password' => 'required|string|min:6',
            'locale' => 'nullable|string|min:2|max:2|in:' . implode(',', config('locales.allowed'))
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 400);

        $auth = User::withTrashed()->firstWhere('email', $request->email);

        // Change locale
        if($request->has('locale'))
            App::setLocale($request->locale);
        
        if($auth){
            // Validate password credentials
            if(Hash::check($request->password, $auth->password)){

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

    /**
     * Register User
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(RegisterRequest $request) 
    {
        try {
            $request->merge(['avatar' => 'default.png']);
            $request->merge(['country_id' => GeolocationController::getIdFromCodeOfCountry($request->country_code)]);
            $request->merge(['password' => Hash::make($request['password'])]);
            $request->merge(['remember_token' => Str::random(10)]);
            
            //Create User
            $user = User::create($request->toArray());

            //Create slug
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower(str_replace(' ','',$request->name).'.'.str_replace(' ','',$request->lastname)));
            if(User::where('slug', $slug)->exists()) $slug = $slug.'.'.$user->id;
            $user->update(['slug' => $slug]);

            //Assign role
            $user->assignRole('User');
            $user['roles'] = $user->getRoleNames();

            return response()->json(['user' => $user], 201);

        } catch(\Exception $e) {
            return response()->json(['errors' => $e], 500);
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
            'email' => 'required|string|email|max:190',
            'locale' => 'nullable|string|min:2|max:2'
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);
        
        $user = User::withTrashed()->firstWhere('email', $request->email);
        $locale = $request->has('locale') && in_array($request->locale, $this->locales) ? $request->locale : 'en';

        //Verify if exists this User
        if(!$user) return response()->json(['errors' => 'The email you entered does not exist in our records.'], 400);

        try {
            //Delete others validation codes
            ForgotPassword::where('user_id', $user->id)->delete();

            //Generate new validation Code
            $code = random_int(100000, 999999);
            $forgotPassword = ForgotPassword::create([
                'code' => $code,
                'user_id' => $user->id
            ]);

            //Add expiration time of code
            $forgotPassword->update(['expire_at' => $forgotPassword->created_at->addDays(2)]);

            Mail::to($request->email)->send(new ForgotPasswordMail($forgotPassword->code, $locale));
            return response()->json(['message' => 'Password recovery email sent successfully.'], 200);

        } catch(\Exception $e) {
            return response()->json(['errors' => $e], 500);
        }
    }

    /**
     * Validate Forgot Password Code
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validateForgotPasswordCode(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'code' => 'required|integer',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        $user = User::withTrashed()->where('email', $request->email)->firstOrFail();
        $forgotPassword = ForgotPassword::where('user_id', $user->id)->where('code', $request->code)->first();

        if($forgotPassword){
            //Verify expire time of the code
            if(now()->toDateTimeString() < $forgotPassword->expire_at) {
                $forgotPassword->update(['is_verified' => true]);
                return response()->json(['message' => 'Code validated successfully!'], 200);
            }
            return response()->json(['errors' => 'The code has expired, request a new code.'], 400);
        }
        return response()->json(['errors' => 'The code you entered does not match your code. Retry.'], 400);
    }

    /**
     * Restore Password
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function restorePassword(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        $user = User::withTrashed()->where('email', $request->email)->firstOrFail();
        $forgotPassword = ForgotPassword::where('user_id', $user->id)->where('is_verified', true)->first();

        //If the code was verified
        if($forgotPassword) {
            // Update Password and delete forgotPassword from tabla
            $user->update(['password' => Hash::make($request->password)]);
            $forgotPassword->delete();

            $user['roles'] = $user->getRoleNames();

            // If the user isn't verified don't return token
            if(!$user->is_verified || $user->deleted_at != null)
                return response()->json(['user' => $user], 200);

            // If everything is correct return user and JWT
            $accessToken = $user->createToken('Xperience Personal Access Client')->accessToken;
            return response()->json(['user' => $user, 'accessToken' => $accessToken], 200);
        }
        return response()->json(['errors' => "There isn't password reset request."], 400);
    }

    /**
     * Email verification
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function emailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'locale' => 'nullable|string|min:2|max:2'
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        $user = User::firstWhere('email', $request->email);
        $locale = $request->has('locale') && in_array($request->locale, $this->locales) ? $request->locale : 'en';

        //Verify if exists this User
        if(!$user) return response()->json(['errors' => 'The email you entered does not exist in our records.'], 400);

        //If user isn't verified then send Email
        if(!$user->is_verified) {
            //Delete others validation codes
            EmailVerification::where('user_id', $user->id)->whereNotNull('code')->delete();

            //Generate new validation code
            $code = random_int(100000, 999999);
            EmailVerification::create([
                'code' => $code,
                'user_id' => $user->id
            ]);
            
            Mail::to($request->email)->send(new VerifyAccountMail($code, $user->name, $locale));
            
            return response()->json(['message' => 'Email verification sent successfully.'], 200);
        }
        return response()->json(['message' => 'This user is verified.'], 200);
    }

    /**
     * Verify Phone Number, send SMS code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function phoneNumberVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'phone_number' => 'required|string',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        //Verify if exists this User
        $user = User::firstWhere('email', $request->email);
        if(!$user) return response()->json(['errors' => 'The email you entered does not exist in our records.'], 400);

        //Verify if the phone number is registered
        $phoneValidate = User::where('phone_number', $request->phone_number)->where('id', '!=', $user->id)->first();
        if($phoneValidate) return response()->json(['errors' => 'The phone number you entered has already been registered.'], 400);

        //If user isn't verified then send SMS
        if(!$user->is_verified) {
            $this->sendSMSTo($request->phone_number);
            EmailVerification::where('user_id', $user->id)->whereNotNull('phone_number')->delete();
            EmailVerification::create([
                'phone_number' => $request->phone_number,
                'user_id' => $user->id
            ]);
            return response()->json(['message' => 'SMS message sent successfully.'], 200);
        }
        return response()->json(['message' => 'This user is verified.'], 200);
    }

    /**
     * Verify code
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function codeVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric',
            'email' => 'required|string|email|max:190',
            'verification_type' => 'required|string|in:email,sms'
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        $phoneNumber = null;

        // Verify if this user is already validated
        $user = User::where('email', $request->email)->firstOrFail();
        if($user->is_verified) return response()->json(['This user is already verified.', 202]);

        $isVerifiedCode = false;

        // Check the verification type
        if($request->verification_type == 'email'){
            //Validate email code
            $emailVerification = EmailVerification::where('user_id', $user->id)->where('code', $request->code)->first();
            if($emailVerification) $isVerifiedCode = true;                
        } else {
            //Validate SMS code
            $phoneVerification = EmailVerification::where('user_id', $user->id)->whereNotNull('phone_number')->first();
            if($phoneVerification){
                $phoneNumber = $phoneVerification->phone_number;
                $verification = $this->verifySMSCode($phoneNumber, $request->code);
                if($verification->valid) $isVerifiedCode = true;
            }
        }
        
        //If is valid code
        if($isVerifiedCode) {
            $data = array('is_verified' => true);
            // If verification type is SMS the save phone number
            if($request->verification_type == 'sms') $data['phone_number'] = $phoneNumber;

            $user->update($data);

            $auth = User::find($user->id);
            $auth['roles'] = $auth->getRoleNames();
            $accessToken = $auth->createToken('Xperience Personal Access Client')->accessToken;
            EmailVerification::where('user_id', $user->id)->delete();
            
            return response()->json(['user' => $auth, 'accessToken' => $accessToken], 200);
        }
        return response()->json(['errors' => 'Invalid verification code entered!'], 403);
    }

    /**
     * User Logout
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request) 
    {
        $token = $request->user()->token()->revoke();
        return response()->json(['message' => 'You have been successfully logged out!'], 204);
    }

    /**
     * Delete user token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteTokens(Request $request) 
    {
        $request->user()->token()->delete();
        return response()->json(['message' => 'You have been successfully logged out from all sessions!'], 204);
    }

    /**
     * Revoke all user token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function revokeAllTokens(Request $request) 
    {
        $tokens = $request->user()->tokens;
        foreach ($tokens as $token) {
            $token->revoke();
        }
        
        return response()->json(['message' => 'You have been successfully logged out from all sessions!'], 204);
    }

    /**
     * Refresh token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function refreshToken(Request $request)
    {
        $token = $request->user()->refresh_token;
        return response()->json(['accessToken' => $token], 200);
    }
}
