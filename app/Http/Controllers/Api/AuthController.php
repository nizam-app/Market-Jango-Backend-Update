<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\JWTToken;
use App\Helpers\ResponseHelper;
use App\Helpers\TwilioService;
use App\Http\Controllers\Controller;
use App\Mail\OTPSend;
use App\Models\Admin;
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
            $userType =  $request->input('user_type');
            if($userType==='admin'){
                $role = $request->role;
                // Create the user
                $user = User::create([
                    'user_type' => 'admin',
                    'name' => $request->name,
                    'email' => $request->email,
                    'status' => 'Approved',
                    'password' => bcrypt($request->password),
                ]);

                // Create admin in separate table
                $admin = Admin::create([
                    'user_id'           => $user->id,
                    'role'              => $role ?? 'admin',
                    'date_of_birth'     => $request->input('date_of_birth'),
                    'present_address'   => $request->input('present_address'),
                    'permanent_address' => $request->input('permanent_address'),
                    'city'              => $request->input('city'),
                    'postal_code'       => $request->input('postal_code'),
                    'country'           => $request->input('country'),
                ]);
                // Assign role via spatie
                $user->assignRole($role??'admin');
                $token = JWTToken::registerToken($user->user_type, $user->id);
                $sendToken = 'Bearer ' . $token;
                return ResponseHelper::Out('success','admin created successfully',['uer'=>$user, 'admin'=> $admin, 'token'=> $sendToken],201)->cookie('token', $sendToken, 525600);
            }
            $user = User::create([
                'user_type' =>$userType,
            ]);
            $token = JWTToken::registerToken($user->user_type, $user->id);
            $sendToken = 'Bearer ' . $token;
           return ResponseHelper::Out('success','User registered successfully',['uer'=>$user, 'token'=> $sendToken],201)->cookie('token', $sendToken, 525600);
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
                'longitude'   => 'nullable',
                'latitude'   => 'nullable',
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
                    'longitude'        => $request->input('longitude'),
                    'latitude'        => $request->input('latitude'),
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
            $user = User::where('email', $request->input('email'))->with('admin')->first();
            if (!$user || !Hash::check($request->input('password'), $user->password)) {
                return ResponseHelper::Out('failed','Invalid email or password',null,401);
            }
            if ($user->status != 'Approved') {
                return ResponseHelper::Out('failed','Please Wait for confirmation',null,200);
            }
            // set token
            $token = JWTToken::loginToken($user->email, $user->id);
            $sendToken = 'Bearer ' . $token;
            return ResponseHelper::Out('success', 'Login successful',['user'=>$user, 'token'=> $sendToken], 200)->cookie('token', $sendToken, 525600);
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
            return ResponseHelper::Out('success', 'Otp verification successful!',['uer'=>$user, 'token'=> $sendToken],200)->cookie('token', $sendToken, 525600);
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
                    'buyer',
                    'driver',
                    'admin',
                    'vendor',
                    'transport'
                ])
                ->select(['id', 'name', 'image', 'public_id', 'user_type', 'email', 'phone', 'phone_verified_at', 'language', 'status'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $userType = $user->user_type;
            $buyer = $user->buyer;
            $driver = $user->driver;
            $vendor = $user->vendor;
            $transport = $user->transport;
            $admin = $user->admin;
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
                    $buyer->update([
                        "gender" => $request->filled('gender') ? $request->input('gender') : $buyer->gender,
                        "age" => $request->filled('age') ? $request->input('age') : $buyer->age,
                        "address" => $request->filled('address') ? $request->input('address') : $buyer->address,
                        "state" => $request->filled('state') ? $request->input('state') : $buyer->state,
                        "postcode" => $request->filled('postcode') ? $request->input('postcode') : $buyer->postcode,
                        "country" => $request->filled('country') ? $request->input('country') : $buyer->country,
                        "ship_name" => $request->filled('ship_name') ? $request->input('ship_name') : $buyer->ship_name,
                        "ship_email" => $request->filled('ship_email') ? $request->input('ship_email') : $buyer->ship_email,
                        "ship_location" => $request->filled('ship_location') ? $request->input('ship_location') : $buyer->ship_location,
                        "ship_latitude" => $request->filled('ship_latitude') ? $request->input('ship_latitude') : $buyer->ship_latitude,
                        "ship_longitude" => $request->filled('ship_longitude') ? $request->input('ship_longitude') : $buyer->ship_longitude,
                        "ship_country" => $request->filled('ship_country') ? $request->input('ship_country') : $buyer->ship_country,
                        "ship_phone" => $request->filled('ship_phone') ? $request->input('ship_phone') : $buyer->ship_phone,
                        "description" => $request->filled('description') ? $request->input('description') : $buyer->description,
                        "location" => $request->filled('location') ? $request->input('location') : $buyer->location,
                    ]);
                    break;
                case 'vendor':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language)
                    ]);
                    $vendorUploadedFile = null;
                    if ($request->hasFile('cover_image')) {
                        $request->validate([
                            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                        ]);
                        // Delete old image if exists
                        if (!empty($vendor->public_id)) {
                            FileHelper::delete($vendor->public_id);
                        }
                        $path = $userType.'/cover_image';
                        //Upload new image
                        $file = $request->file('cover_image');
                        $vendorUploadedFile = FileHelper::upload($file, $path);
                        $vendor->cover_image = $vendorUploadedFile[0]['url'];
                        $vendor->public_id = $vendorUploadedFile[0]['public_id'];
                        $vendor->save();
                    }
                $vendor->update([
                        "country" => $request->input('country', $vendor->country),
                        "address" => $request->input('address', $vendor->address),
                        "open_time" => $request->input('open_time', $vendor->open_time),
                        "close_time" => $request->input('close_time', $vendor->close_time),
                        "business_name" => $request->input('business_name', $vendor->business_name),
                        "longitude" => $request->input('longitude', $vendor->longitude),
                        "latitude" => $request->input('latitude', $vendor->latitude),
                        "business_type" => $request->input('business_type', $vendor->business_type)
                    ]);
                    break;
                case 'transport':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language)
                    ]);
                    $transport->update([
                        "address" => $request->input('address', $user->address),
                        "longitude" => $request->input('longitude', $user->longitude),
                        "latitude" => $request->input('latitude', $user->latitude),
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
                case 'admin':
                    $user->update([
                        "name" => $request->input('name', $user->name),
                        "language" => $request->input('language', $user->language)
                    ]);
//                    if ($admin) {
//                        $admin->update([
//                            "role" => $request->input('role', $admin->role),
//                            'date_of_birth'      => $request->input('date_of_birth', $admin->date_of_birth),
//                            'present_address'    => $request->input('present_address', $admin->present_address),
//                            'permanent_address'  => $request->input('permanent_address', $admin->permanent_address),
//                            'city'               => $request->input('city', $admin->city),
//                            'postal_code'        => $request->input('postal_code', $admin->postal_code),
//                            'country'            => $request->input('country', $admin->country)
//                        ]);
//                    }
                    break;
                default:
                    break;
            }

            return ResponseHelper::Out('success', 'user update successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //UPDATE USER ONLINE STATUS
    public function heartbeat(Request $request)
    {
        $user = User::where('id', $request->header('id'))
            ->first();
        if (!$user) {
            return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
        }
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Avoid too many writes
        if (!$user->last_active_at || $user->last_active_at->lt(now()->subSeconds(30))) {
            $user->update([
                'last_active_at' => now()
            ]);
        }
        return ResponseHelper::Out('success', 'user online status update successfully', null, 200);
    }
    // CHECK STATUS API
    public function getstatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'is_online' => $user->is_online,
            'last_seen' => $user->last_seen,
        ]);
    }
    // ADMIN SET PASSWORD
    public function adminResetPassword(Request $request)
    {
        try {

            $request->validate([
                'old_password' => 'required|string|min:8',
                'email' => 'required',
                'password' => 'required|string|min:8',
            ]);
            $user = User::where('email', $request->input('email'))->first();
            if (!$user || !Hash::check($request->input('old_password'), $user->password)) {
                return ResponseHelper::Out('failed','Invalid email or password',null,401);
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
    //USER LOGOUT
    public function logout(Request $request)
    {
        return ResponseHelper::Out('success', 'Logout successful', null, 200);
    }
}
