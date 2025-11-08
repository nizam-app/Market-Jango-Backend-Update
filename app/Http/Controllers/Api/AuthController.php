<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\JWTToken;
use App\Helpers\ResponseHelper;
use App\Helpers\TwilioService;
use App\Http\Controllers\Controller;
use App\Mail\OTPSend;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Transport;
use App\Models\User;
use App\Models\UserImage;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //get vendor type
    public function businessType(Request $request): JsonResponse
    {
        try {

            $deliveryCharges = [
                'Restaurant',
                'Grocery',
                'Pharmacy',
                'Electronics',
                'Clothing',
                'Hardware',
            ];
            return ResponseHelper::Out('success', 'All delivery charge successfully fetched', $deliveryCharges, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //get language
    public function language(Request $request): JsonResponse
    {
        try {
            $languages = [
                'English',
                'Français',
                'Русский',
                'Tiếng Việt'
            ];
            return ResponseHelper::Out('success', 'All language successfully fetched', $languages, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
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
           return ResponseHelper::Out('success','User registered successfully',['uer'=>$user, 'token'=> $sendToken],201);
        } catch (ValidationException $e) {
           return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
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
                'phone' => 'required|string|max:15|regex:/^\+[1-9]\d{1,14}$/'
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
            // Driver check
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
            $otp = rand(100000, 999999);
            $phone = $request->input('phone');
//            $sms = new TwilioService();
//            $sms->sendSms($request->phone, "Your OTP code is: $otp");
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
                'otp' => 'required|max:6'
            ]);
            $userId = $request->header('id');
            $user = User::where('id', '=', $userId)->first();
            if(!$user){
               return ResponseHelper::Out('failed','User not found',null, 404);
            }
            $otp = $request->input('otp');
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
                'password' => Hash::make($request->input('password'))
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
                $user->update(['status' => 'Approved']);
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
                'files'   => 'nullable|array',
                'files.*' => 'nullable|file|mimes:jpg,jpeg,png,avif,webp,pdf,doc,docx,xls,xlsx|max:10240'
            ]);

            $userId = $request->header('id');
            $user = User::find($userId);

            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            // Vendor create or update
            $vendor = Vendor::updateOrCreate(
                ['user_id' => $userId],
                [
                    'country'        => $request->input('country'),
                    'business_name'  => $request->input('business_name'),
                    'business_type'  => $request->input('business_type'),
                    'address'        => $request->input('address'),
                ]
            );
            if ($request->hasFile('files')) {
                $oldImages = UserImage::where('user_type', 'vendor')->where('user_id', $vendor->id)->get();
                if ($oldImages->count() > 0) {
                    foreach ($oldImages as $old) {
                        if (!empty($old->public_id)) {
                            FileHelper::delete($old->public_id);
                        }
                        $old->delete();
                    }
                }

                $files = $request->file('files');
                $uploadedFiles = FileHelper::upload($files, $user->user_type);

                foreach ($uploadedFiles as $file) {
                    UserImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'user_id'    => $vendor->id,
                        'user_type'  => $user->user_type,
                        'file_type'  => 'image'
                    ]);
                }
            }



            return ResponseHelper::Out('success', 'Vendor registered successfully!', $vendor, 201);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('error', 'Validation Failed', $e->errors(), 422);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
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
                $driver = Driver::updateOrCreate(
                    ['user_id' => $userId],
                    [
                    'car_name'=> $request->input('car_name'),
                    'car_model' => $request->input('car_model'),
                    'location' => $request->input('location'),
                    'price' => $request->input('price'),
                    'user_id' => $userId,
                    'route_id' => $request->input('route_id'),
                ]);
            if ($request->hasFile('files')) {
                $oldImages = UserImage::where('user_type', 'driver')->where('user_id', $driver->id)->get();
                if ($oldImages->count() > 0) {
                    foreach ($oldImages as $old) {
                        if (!empty($old->public_id)) {
                            FileHelper::delete($old->public_id);
                        }
                        $old->delete();
                    }
                }
                $files = $request->file('files');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, $userType);
                foreach ($uploadedFiles as $file) {
                    UserImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'user_id'    => $driver->id,
                        'user_type'  => $userType,
                        'file_type'  => 'image'
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
            $otp = rand(100000, 999999);
            Mail::to($email)->send(new OTPSend($otp,$user->title));
            $user->update(['otp' => $otp,'expires_at' => Carbon::now()->addMinutes(10)]);
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
                'otp' => 'required|min:6'
            ]);
            $email = $request->input('email');
            $otp = $request->input('otp');
            $user = User::where('email', '=', $email)->where('otp', '=', $otp)->first();
            if ($user == null) {
                return ResponseHelper::Out('failed','unauthorized',null,401);
            }
            $token = JWTToken::resetToken($email, $user->id);
            $sendToken = 'Bearer ' . $token;
            $user->update(['otp' => '0']);
            return ResponseHelper::Out('success', 'Otp verification successful!',['uer'=>$user, 'token'=> $sendToken],200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //user password reset
    public function resetPassword(Request $request)
    {
        try {

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
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
            'password' => Hash::make($request->input('password'))
        ]);
        return ResponseHelper::Out('success', 'Password set successful!',$user,200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('error','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    //update
    public function update(Request $request): JsonResponse
    {
        try {
            $user = User::where('id', $request->header('id'))
                ->with([
                    'buyer:id,age,gender,address,description,state,country,user_id',
                    'driver:id,price,user_id',
                    'vendor:id,user_id',
                    'transport:id,user_id'
                ])
                ->select(['id', 'name', 'image', 'public_id', 'user_type', 'email', 'phone', 'phone_verified_at', 'language', 'status'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $userType = $user->user_type;
            $buyer = $user->buyer;
            $driver = $user->driver;
            $uploadedFile = null;
            if ($request->hasFile('image')) {
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                // Delete old image if exists
                if (!empty($user->public_id)) {
                    FileHelper::delete($user->public_id);
                }
                //Upload new image
                $file = $request->file('image');
                $uploadedFile = FileHelper::upload($file, $userType);
                $user->image = $uploadedFile[0]['url'];
                $user->public_id = $uploadedFile[0]['public_id'];
                $user->save();
            }
            switch ($userType) {
                case 'buyer':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language)
                    ]);
//                    $buyer->update([
//                        "gender" => $request->input('gender', $buyer->gender),
//                        "age" => $request->input('age', $buyer->age),
//                        "address" => $request->input('address', $buyer->address),
//                        "state" => $request->input('state', $buyer->state),
//                        "postcode" => $request->input('postcode', $buyer->postcode),
//                        "country" => $request->input('country', $buyer->country),
//                        "ship_name" => $request->input('ship_name', $buyer->ship_name),
//                        "ship_email" => $request->input('ship_email', $buyer->ship_email),
//                        "ship_address" => $request->input('ship_address', $buyer->ship_address),
//                        "ship_city" => $request->input('ship_city', $buyer->ship_city),
//                        "ship_state" => $request->input('ship_state', $buyer->ship_state),
//                        "ship_country" => $request->input('ship_country', $buyer->ship_country),
//                        "ship_phone" => $request->input('ship_phone', $buyer->ship_phone),
//                        "description" => $request->input('description', $buyer->description),
//                        "location" => $request->input('location', $buyer->location)
//                    ]);
                    $buyer->update([
                        "gender" => $request->filled('gender') ? $request->input('gender') : $buyer->gender,
                        "age" => $request->filled('age') ? $request->input('age') : $buyer->age,
                        "address" => $request->filled('address') ? $request->input('address') : $buyer->address,
                        "state" => $request->filled('state') ? $request->input('state') : $buyer->state,
                        "postcode" => $request->filled('postcode') ? $request->input('postcode') : $buyer->postcode,
                        "country" => $request->filled('country') ? $request->input('country') : $buyer->country,
                        "ship_name" => $request->filled('ship_name') ? $request->input('ship_name') : $buyer->ship_name,
                        "ship_email" => $request->filled('ship_email') ? $request->input('ship_email') : $buyer->ship_email,
                        "ship_address" => $request->filled('ship_address') ? $request->input('ship_address') : $buyer->ship_address,
                        "ship_city" => $request->filled('ship_city') ? $request->input('ship_city') : $buyer->ship_city,
                        "ship_state" => $request->filled('ship_state') ? $request->input('ship_state') : $buyer->ship_state,
                        "ship_country" => $request->filled('ship_country') ? $request->input('ship_country') : $buyer->ship_country,
                        "ship_phone" => $request->filled('ship_phone') ? $request->input('ship_phone') : $buyer->ship_phone,
                        "description" => $request->filled('description') ? $request->input('description') : $buyer->description,
                        "location" => $request->filled('location') ? $request->input('location') : $buyer->location,
                    ]);

                    break;
                case 'vendor':
                case 'transport':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language)
                    ]);
                    break;
                case 'driver':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language),
                        "is_active" => $request->input('is_active', $user->is_active)
                    ]);
                    $driver->update([
                        "price" => $request->input('price', $driver->price),
                    ]);
                    break;
                default:
                    break;
            }

            return ResponseHelper::Out('success', 'user update successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
