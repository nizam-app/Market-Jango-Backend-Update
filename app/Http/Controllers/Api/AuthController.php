<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\JWTToken;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\OTPSend;
use App\Models\Banner;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Image;
use App\Models\Transport;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //store user type
    public function registerType(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'user_type' => 'required|in:buyer,vendor,driver,transport,admin',
            ]);
            $user = User::create([
                'user_type' => $request->input('user_type'),
            ]);
            $token = JWTToken::registerToken($user->user_type, $user->id);
            $sendToken = 'Bearer ' . $token;
            $user->update([
                'token' => $sendToken
            ]);
           return ResponseHelper::Out('success','User registered successfully',['uer'=>$user, 'token'=> $sendToken],201);
        } catch (ValidationException $e) {
           return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //store title witch user create type \$e\-\>getMessage\(\) \$e\-\>errors\(\)
    public function registerName(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string',
            ]);
            $userId = $request->header('id');
            $user = User::where('id', $userId)->first();
            if(!$user){
                return ResponseHelper::Out('failed','User not found',null, 404);
            }
            $user->update([
                'name' => $request->input('name'),
            ]);
            return ResponseHelper::Out('success','User Title set successful!',$user, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
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
            $user = User::where('id', $userId)->first();
            if(!$user){
               return ResponseHelper::Out('failed','User not found',null, 404);
            }
            $userType = $user->user_type;
            if ($userType === 'vendor') {
                $vendor = Vendor::where('user_id', $userId)->first();
                if (!$vendor) {
                    return ResponseHelper::Out(
                        'failed',
                        'Please complete your store setup before setting your phone number.',
                        null,
                        400
                    );
                }
            }

            // ✅ Driver check
            if ($userType === 'driver') {
                $driver = Driver::where('user_id', $userId)->first();
                if (!$driver) {
                    return ResponseHelper::Out(
                        'failed',
                        'Please complete your car information registration before setting your phone number.',
                        null,
                        400
                    );
                }
            }
            // OTP generate
            $otp = rand(10000000, 99999999);
            Log::info("Generated OTP for {$request->phone}: $otp");
            $phone = $request->input('phone');
            $user->update([
                'phone' => $phone,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]);
            return ResponseHelper::Out('success','OTP sent to phone',$user, 200)->header('phone', $phone);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
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
            if(!$user){
               return ResponseHelper::Out('failed','User not found',null, 404);
            }
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
            return ResponseHelper::Out('success','OTP verify successful!',$user, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //store email
    public function registerEmail(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email'
            ]);
            $email = $request->input('email');
            $userId = $request->header('id');
            $user = User::where('id', $userId)->first();
            if(!$user){
               return ResponseHelper::Out('failed','User not found',null, 404);
            }
            if($user->phone_verified_at == Null){
                return ResponseHelper::Out('failed','Please first verify your phone number',null, 200);
            }
            $user->update([
                'email' => $email
            ]);
            return ResponseHelper::Out('success','User Email set successful!',$user, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //set password
    public function registerPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'password'  => 'required|string|min:6|confirmed',
            ]);
            $userId = $request->header('id');
            $user = User::where('id', $userId)->first();
            $confirmMessage       = "Your Account has been successfully created";
            $congratulationMessage= "Congratulations!";
            $reviewMessage        = "Your Account has been under review";
            $waitMessage          = "Wait for confirmation";
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            //set password
            $user->update([
                'password' => Hash::make($request->input('password')),
                'token'=> null
            ]);
            //update status and throw message
            if ($user->user_type === 'buyer') {
                Buyer::create([
                    'user_id' => $user->id,
                ]);
                // Auto-approve
                $user->update(['status' => 'Approved']);
                $title    = $congratulationMessage;
                $subtitle = $confirmMessage;
            } elseif ($user->user_type === 'transport'){
                Transport::create([
                    'user_id' => $user->id,
                ]);
                $title    = $congratulationMessage;
                $subtitle = $confirmMessage;
            }
            elseif ($user->user_type === 'vendor' || $user->user_type === 'driver') {
                // Under review
                $user->update(['status' => 'Pending']);
                $title    = $waitMessage;
                $subtitle = $reviewMessage;
            } else {
                return ResponseHelper::Out('error', 'Invalid user type', [
                    'user_type' => $user->user_type
                ], 400);
            }
            $payload = [
                'user'     => $user->fresh(),
                'title'    => $title,
                'subtitle' => $subtitle,
            ];
            return ResponseHelper::Out('success', 'Password set successfully!', $payload, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error', 'Validation Failed', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //vendor sotre
    public function registerVendor(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'country'        => 'required',
                'business_name'  => 'required|string',
                'business_type'  => 'required|in:Restaurant,Grocery,Pharmacy,Electronics,Clothing,Hardware',
                'address'        => 'required|string',
                'files.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240'
            ]);
            $userId = $request->header('id');
            $user = User::where('id', $userId)->first();
            $userType = $user->user_type;
            if(!$user){
               return ResponseHelper::Out('failed','User not found',null, 404);
            }
            //vendor store
            $vendor = Vendor::create([
                'country'        => $request->input('country'),
                'business_name'  => $request->input('business_name'),
                'business_type'  => $request->input('business_type'),
                'address'        => $request->input('address'),
                'user_id'        => $userId,
            ]);
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, $userType);
                foreach ($uploadedFiles as $f) {
                    Image::create([
                        'image_path' => 'storage/' . $f['path'],
                        'user_id'    => $vendor->id,
                        'user_type'  => $userType,
                        'file_type'  => $f['type'],
                    ]);
                }
            }
            return ResponseHelper::Out('success', 'Vendor registered successfully!', $vendor, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }

    //driver store
    public function registerDriver(Request $request):JsonResponse
    {
        try {
                $request->validate([
                    'car_name'  => 'required|string',
                    'car_model'  => 'required|string',
                    'location'  => 'required|string',
                    'price'  => 'required|string',
                    'files.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240'
                ]);
                $userId = $request->header('id');
                $user = User::where('id', $userId)->first();
                $userType= $user->user_type;
                if(!$user){
                    return ResponseHelper::Out('failed','User not found',null, 404);
                }
                //multiple image upload
                $driver = Driver::create([
                    'car_name'=> $request->input('car_name'),
                    'car_model' => $request->input('car_model'),
                    'location' => $request->input('location'),
                    'price' => $request->input('price'),
                    'user_id' => $userId,
                    'route_id' => $request->input('route_id'),
                ]);
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, $userType);
                foreach ($uploadedFiles as $f) {
                    Image::create([
                        'image_path' => 'storage/' . $f['path'],
                        'user_id'    => $driver->id,
                        'user_type'  => $userType,
                        'file_type'  => $f['type'],
                    ]);
                }
            }
            return ResponseHelper::Out('success', 'Driver registered successfully!', $driver, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //user login
    public function login(Request $request):JsonResponse
    {
        try{
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);
            $user = User::where('email', $request->input('email'))->first();
            if (!$user || !Hash::check($request->input('password'), $user->password)) {
                return ResponseHelper::Out('failed','Invalid email or password',null,401);
            }
            if ($user->status != 'Approved') {
                return ResponseHelper::Out('failed','Please Wait for confirmation',null,200);
            }
            // set token
            $token = JWTToken::loginToken($user->email, $user->id);
            $sendToken = 'Bearer ' . $token;
            $user->update([
                'token' => $sendToken
            ]);
            return ResponseHelper::Out('success', 'Login successful',['uer'=>$user, 'token'=> $sendToken], 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    // send otp
    public function forgetPassword(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'email'    => 'required'
            ]);
            $email= $request->input('email');
            $user = User::where('email', '=', $email)->first();
            if ($user == null) {
                return ResponseHelper::Out('failed','unauthorized',null,401);
            }
            $otp = rand(10000000, 99999999);
            Mail::to($email)->send(new OTPSend($otp,$user->title));
            $user->update(['otp' => $otp]);
            return ResponseHelper::Out('success', 'OTP sent to your registered mail',$otp,200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //verify otp
    public function verifyMailOtp(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'otp' => 'required|min:8'
            ]);
            $email = $request->input('email');
            $otp = $request->input('otp');
            $user = User::where('email', '=', $email)->where('otp', '=', $otp)->first();
            if ($user == null) {
                return ResponseHelper::Out('failed','unauthorized',null,401);
            }
            $token = JWTToken::resetToken($email, $user->id);
            $sendToken = 'Bearer ' . $token;
            $user->update([
                'token' => $sendToken
            ]);
            $user->update(['otp' => '0']);
            return ResponseHelper::Out('success', 'Otp verification successful!',['uer'=>$user, 'token'=> $sendToken],200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
//    user password reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);
        $email = $request->header('email');
        $id = $request->header('id');
        $user = User::where('email', '=', $email)->where('id','=', $id)->first();
        if (!$user) {
            if ($user == null) {
                return ResponseHelper::Out('failed','unauthorized',null,401);
            }
        }
        //update password
        $user->update([
            'password' => Hash::make($request->input('password')),
            'token' => null
        ]);
        return ResponseHelper::Out('success', 'Password set successful!',$user,200);
    }
    //all user
    public function index(): JsonResponse
    {
        try {
            $banners = User::with('vendor','buyer','driver','transport')
                ->select(['id','name','user_image','email','phone','user_type','language','status','phone_verified_at'])
                ->paginate(20);
            return ResponseHelper::Out('success', 'All banners successfully fetched', $banners, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
