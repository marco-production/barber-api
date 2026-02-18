<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialAuthController extends Controller
{

    public function __construct(
        private AuthService $auth_service,
    ) 
    {}
 
    /**
     * Login with Google Account
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if($validator->fails()) 
            return response()->json(['errors' => $validator->errors()->all()], 400);

        try {

            $result = $this->auth_service->loginWithGoogle($validator->validated(), $request->userAgent(), $request->ip());

            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
