<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\Eticket\Eticket;
use App\Models\Eticket\Traveler;
use App\Models\UserEntities\EmailVerification;
use App\Models\UserEntities\UserLogs;
use App\Http\Controllers\Api\GeolocationController;
use App\Mail\EmailUpdateMail;
use App\Traits\PhoneVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Image;

class AuthUserController extends Controller
{
    use PhoneVerification;
    private $locales = ['en', 'es'];

    /**
     * Get Auth user
     * 
     * @return \Illuminate\Http\Response
     */
    public function authUser()
    {
        $user = Auth::user();
        $user['roles'] = $user->getRoleNames();
        return response()->json($user, 200);
    }

    /**
     * Update user profile
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required_with:lastname|string|max:190',
            'lastname' => 'required_with:name|string|max:190',
            'country_code' => 'string|max:2',
            'avatar' => 'image|mimes:png,jpg,jpeg',
            'email' => 'string|email|max:190',
            'password' => 'string|min:6',
            'current_password' => 'required_with:password|string|current_password:api|min:6',
        ], $messages = [
            'current_password.current_password' => "The current password isn't correct."
        ]);

        if($validator->fails()) return response(['errors' => $validator->errors()->all()], 422);

        $auth = Auth::user();
        $mainTraveler = Traveler::where('user_id', $auth->id)->where('principal',true)->first();

        if($request->has('email')){
            $usersEmail = User::where('email', $request->email)->where('email', '!=', $auth->email)->count();
            if(($auth->email == $request->email) || $usersEmail > 0) return response(['errors' => 'The email entered is already in use.'], 400);
            $auth->email = $request->email;
        }

        if($request->has('password')) $auth->password = Hash::make($request->password);

        if($request->has(['name', 'lastname']) && ($auth->name != $request->name || $auth->lastname != $request->lastname)){
            $slug = strtolower(str_replace(' ','',$request->name).'.'.str_replace(' ','',$request->lastname));
            if(User::where('slug', $slug)->exists()) $slug = $slug.'.'.$auth->id;
            $auth->slug = $slug;
            $auth->name = $request->name;
            $auth->lastname = $request->lastname;
        }

        if($request->hasFile('avatar') && $request->file('avatar')->isValid()){
            //Delete current avatar
            if($auth->avatar != 'default.png'){
                $delete_avatar = public_path().'/images/avatar/'.$auth->avatar; 
                File::delete($delete_avatar);
            }

            // File name
            $file = $request->avatar;
            $name = time().'.'.$file->extension();
            $originalPath = public_path().'/images/avatar/';
            // Resize and save image
            $thumbnailImage = Image::make($file);
            $thumbnailImage->orientate(); // <-- Para mantener la orientacion de la imagen
            if($thumbnailImage->height() > 270 || $thumbnailImage->width() > 270){
                $thumbnailImage->resize(270, 270);
            }
            $thumbnailImage->save($originalPath.$name);
            $auth->avatar = $name;
        }

        $auth->country_id = $request->has('country_code') ? GeolocationController::getIdFromCodeOfCountry($request->country_code) : $auth->country_id;

        if($mainTraveler){
            $mainTraveler->update([
                'name' => $auth->name,
                'lastname' => $auth->lastname,
            ]);  
        }

        $auth->save();
        $auth['roles'] = $auth->getRoleNames();

        return response()->json($auth, 200);
    }

    /**
     * Reset Password
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'current_password' => 'required_with:password|current_password:api|string|min:6',
            'locale' => 'nullable|string|min:2|max:2'
        ], $messages = [
            'current_password.current_password' => __('current_password_is_not_correct')
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        // Change locale
        if($request->has('locale') && in_array($request->locale, $this->locales))
            App::setLocale($request->locale);

        $auth = $request->user();
        $auth->update(['password' => Hash::make($request->password)]);
        return response(['message' => 'Password reseted successfully.'], 200);
    }

    /**
     * Update Email
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'string|email|max:190',
            'locale' => 'nullable|string|min:2|max:2'
        ]);

        if($validator->fails()) return response(['errors' => $validator->errors()->all()], 422);
        
        $auth = Auth::user();
        $usersEmail = User::where('email', $request->email)->where('email', '!=', $auth->email)->count();
        
        // Verify if there are any user with this Email
        if(($auth->email == $request->email) || $usersEmail > 0)
            return response(['errors' => 'The email entered is already in use.'], 400);

        // Verify locale
        $locale = $request->has('locale') && in_array($request->locale, $this->locales) ? $request->locale : 'en';    

        //Delete others validation codes
        EmailVerification::where('user_id', $auth->id)->delete();

        //Generate new validation code
        $code = random_int(100000, 999999);
        EmailVerification::create([
            'code' => $code,
            'email' => $request->email,
            'user_id' => $auth->id
        ]);
        
        try {
            Mail::to($request->email)->send(new EmailUpdateMail($code, $auth->name, $locale));
            return response(['message' => 'Verification mail sent successfully.'], 200);

        } catch(\Exception $e) {
            return response()->json(['errors' => $e], 500);
        }
    }

    /**
     * Update Email - Verification code
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function emailCodeVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        $auth = Auth::user();

        //Validate email code
        $emailVerification = EmailVerification::where('user_id', $auth->id)->where('code', $request->code)->first();
        if($emailVerification){
            $auth->update(['email' => $emailVerification->email]);
            $emailVerification->delete();
            return response(['message' => 'Email updated successfully.', 'email' => $auth->email], 200);
        } 

        return response()->json(['errors' => 'Invalid verification code entered.'], 403);
    }

    /**
     * Update Phone Number
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        // Verify if the phone number is different to current
        if(Auth::user()->phone_number == $request->phone_number)
            return response()->json(['errors' => 'The phone number you entered is the same as registered.'], 400);

        // Verify if the phone number is registered
        $phoneValidate = User::where('phone_number', $request->phone_number)->where('id', '!=', Auth::user()->id)->first();
        if($phoneValidate) return response()->json(['errors' => 'The phone number you entered has already been registered.'], 400);

        // Send SMS code verification
        $this->sendSMSTo($request->phone_number);
        return response(['message' => 'SMS message sent successfully.'], 200);
    }

    /**
     * Code to account verification
     * SMS
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function phoneNumberCodeVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'code' => 'required|numeric'
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        //Validate SMS code
        $verification = $this->verifySMSCode($request->phone_number, $request->code);

        if($verification->valid){
            Auth::user()->update(['phone_number' => $request->phone_number]);
            return response(['message' => 'Phone number updated successfully.'], 200);
        }

        return response()->json(['errors' => 'Invalid verification code entered.'], 403);
    }

    /**
     * Activate or deactivated User Account
     * 
     * @return \Illuminate\Http\Response
     * @param  \Illuminate\Http\Request  $request
     */
    public function enableAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:190',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 400);

        $auth = User::onlyTrashed()->firstWhere('email', $request->email);

        if($auth){
            // Validate password credentials
            if(Hash::check($request->password, $auth->password)){

                if(!$auth->active){
                    return response(['message' => __('disabled_user')], 422); 
                }
                
                // Verify if the user have the necessary roles to login
                if($auth->hasAnyRole(['User', 'Admin', 'Super Admin'])) {
                    $auth->restore();
                    $auth['roles'] = $auth->getRoleNames();
                    $auth->makeHidden(['created_at', 'updated_at']);
                    $accessToken = $auth->createToken('Xperience Personal Access Client')->accessToken;
                    
                    return response()->json(['user' => $auth, 'accessToken' => $accessToken], 200);
                }
                return response(['errors' => "You don't have permission to login."], 404);
            } 
            return response(['errors' => 'Password mismatch.'], 422);
        } 
        return response(['errors' => "User doesn't exist or not match with Trashed records."], 404);
    }

    /**
     * Deactivated User Account
     */
    public function disableAccount()
    {
        $auth = Auth::user();

        // Revoke all tokens
        $tokens = $auth->tokens;
        foreach ($tokens as $token) {
            $token->revoke();
        }

        // Deactivate account
        $auth->delete();
        return response()->json(['message' => 'Account deactivated successfully.'], 200);
    }

    /**
     * Delete Auth User permanently
     * 
     * @return \Illuminate\Http\Response
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason_id' => 'nullable|integer',
        ]);

        if($validator->fails()) return response()->json(['errors' => $validator->errors()->all()], 422);

        // Get Auth
        $user = Auth::user();
        
        // Set NULL to user_id of etickets and travelers records of this User
        Eticket::where('user_id', $user->id)->update(['user_id' => NULL]);
        Traveler::where('user_id', $user->id)->update(['user_id' => NULL, 'deleted_at' => Carbon::now()]);

        // Delete User permanently
        $user->forceDelete();

        // Save the reason of delelete
        if($request->has('reason_id')){
            $userLogs = UserLogs::firstWhere('user_id', $user->id)->update(['reason_id' => $request->reason_id]);
        }
        
        return response()->json(['message' => 'Account deleted successfully.'], 200);
    }
}
