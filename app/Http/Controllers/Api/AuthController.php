<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\JWTToken;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{


    //store user type
    public function registerType(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'user_type' => 'required|in:Buyer,Vendor,Driver,Transport,Admin',
            ]);
            $user = User::create([
                'user_type' => $request->input('user_type'),
            ]);
            $token = JWTToken::registerToken($user->user_type, $user->id);
            $user->update([
                'token' => 'Bearer ' . $token,
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'User Type set successful!',
                'data' => [
                    'user' => $user,
                    'token'=>$token
                ]
            ], 201)->header('token', 'Bearer ' . $token);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //store title witch user create type
    public function registerName(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string',
            ]);
            $userId = $request->header('id');
            $user = User::findOrFail($userId);
            $user->update([
                'name' => $request->input('name'),
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'User Title set successful!',
                'data' => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //store number and send opt
    public function registerPhone(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'phone' => 'required|regex:/^[0-9]+$/|min:11|max:15'
            ]);
            $userId = $request->header('id');
            $user = User::findOrFail($userId);
            // OTP generate
            $otp = rand(10000000, 99999999);
            Log::info("Generated OTP for {$request->phone}: $otp");
            $phone = $request->input('phone');
            $user->update([
                'phone' => $phone,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinute() //only 1 minute valid
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'OTP sent to phone',
                'data' => [
                    'phone' => $user->phone,
                    'otp'   => $otp
                ]
            ], 200)->header('phone', $phone);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //verify phone otp
    public function verifyOtp(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'otp' => 'required'
            ]);
            $phone = $request->header('phone');
            $userId = $request->header('id');
            $user = User::where('phone', $phone)->where('id', '=', $userId)->first();
            $otp = $request->input('otp');
            if (!$user) {
                return response()->json(['status' => 'Fail', 'message' => 'User not found'], 404);
            }

            if ($user->otp != $otp) {
                return response()->json(['status' => 'Fail', 'message' => 'Invalid OTP'], 400);
            }
            //check otp valid
            if (Carbon::parse($user->expires_at)->isPast()) {
                return response()->json(['status' => 'Fail', 'message' => 'OTP expired'], 400);
            }
            $user->update([
                'phone_verified_at' => now(),
                'otp' => null,
                'expires_at' => null
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'OTP verify successful!',
                'data' => [
                    'phone' => $user->phone,
                    'otp'   => $otp
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //store email
    public function registerEmail(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email'
            ]);
            $userId = $request->header('user_id');
            $user = User::findOrFail($userId);
            $email = $request->input('email');
            $user->update([
                'email' => $email
            ]);
            return response()->json([
                'status' => 'Success',
                'message' => 'User Email set successful!',
                'data' => $user
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //set password
    public function registerPassword(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'password'  => 'required|string|min:6|confirmed',
            ]);
            $userId = $request->header('user_id');
            $user = User::findOrFail($userId);
            $userType = $user->user_type;
            //create hash Password
            $password = Hash::make($request->input('password'));
            $user->update([
                'password' => $password
            ]);
            //buyer auto active
            if ($userType === 'transport') {
                $user->update([
                    'status' => 'Active'
                ]);
            }
            //transport auto active
            if ($userType === 'buyer') {
                $user->update([
                    'status' => 'Active'
                ]);
            }
            return response()->json([
                'status' => 'Success',
                'message' => 'User Password set successful!',
                'data' => $user
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //vendor sotre
    public function registerVendor(Request $request):JsonResponse
    {
        try {
            $userId = $request->header('user_id');
            $user = User::findOrFail($userId);
            $userType = $user->user_type;
            if ($userType === 'vendor') {
                $request->validate([
                    'country'  => 'required|string',
                    'business_name'  => 'required|string',
                    'business_type'  => 'required|string',
                    'address'  => 'required|string',
                    'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
                ]);
                //multiple image upload
                $documentPath = FileHelper::upload($request->file('document'), 'vendors');
                $vendor = Vendor::create([
                    'country'=> $request->input('country'),
                    'business_name' => $request->input('business_name'),
                    'business_type' => $request->input('business_type'),
                    'address' => $request->input('address'),
                    'document' => $documentPath
                ]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Vendor set successful!',
                    'data' => $vendor,
                ], 201);
            }
            return response()->json([
                'status' => 'Fail',
                'message' => 'You are not Vendor',
            ], 401);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //driver store
    public function registerDriver(Request $request):JsonResponse
    {
        try {
            $userId = $request->header('user_id');
            $user = User::findOrFail($userId);
            $userType = $user->user_type;
            if ($userType === 'driver') {
                $request->validate([
                    'car_brand'  => 'required|string',
                    'car_model'  => 'required|string',
                    'location'  => 'required|string',
                    'address'  => 'required|string',
                    'price'  => 'required|string',
                    'route_id'  => 'required|string',
                    'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
                ]);
                //multiple image upload
                $documentPath = FileHelper::upload($request->file('document'), 'drivers');
                $driver = Driver::create([
                    'car_brand'=> $request->input('car_brand'),
                    'car_model' => $request->input('car_model'),
                    'location' => $request->input('location'),
                    'address' => $request->input('address'),
                    'price' => $request->input('price'),
                    'route_id' => $request->input('route_id'),
                    'document' => $documentPath
                ]);
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Driver set successful!',
                    'data' => $driver,
                ], 201);
            }
            return response()->json([
                'status' => 'Fail',
                'message' => 'You are not driver',
            ], 401);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //user login
    public function login(Request $request):JsonResponse
    {
        try{
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);
            $user = User::where('email', $request->input('email'))->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'Fail',
                    'message' => 'Invalid email or password'
                ], 401);
            }
            // set token
            $token = JWTToken::CreateToken($email, $user->id);
            // Everything okay
            return response()->json([
                'status' => "Success",
                'message' => 'Login successful with token check',
                'user' => $user,
                'token' => $token
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //forget password
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Random token generate
        $token = Str::random(60);

        // Token save in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );
        // sms send??
        return response()->json([
            'status' => 'Success',
            'message' => 'Password reset token generated',
            'token' => $token
        ]);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // DB থেকে token check
        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetData) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Password update (hashed)
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Token remove after reset
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successful!'
        ]);
    }
}
