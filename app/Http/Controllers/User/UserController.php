<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Services\SquadcoService;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Image\ImageController;
use App\Http\Controllers\Email\Mailer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class UserController extends Controller
{
    /**
     * saves a users password and email instance
     *
     * @return bool
     */
    public static function save($request)
    {
        $save = new User;
        $save->first_name = $request['first_name'];
        $save->last_name = $request['last_name'];
        $save->email = $request['email'];
        $save->password = Hash::make($request['password']);
        $save->status = 'active';
        $save->is_verified = 'yes';
        $save->mobile_number = $request['mobile_number'];
        if (Schema::hasColumn('users', 'date_of_birth')) {
            $save->date_of_birth = $request['date_of_birth'] ?? null;
        }
        $save->user_type = $request['user_type'];

        if ($request->hasFile('profile_image')) {
            $save->avatar = $request->file('profile_image')
                ->store('avatars', 'public');
        }

        $save->save();
        // event(new UserSignUp($save));
        // \Event::dispatch(new UserSignUp($save));
        return $save->id;
    }



    /**
     * Store a newly created resource in db.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     **/
    public function create(Request $request)
    {
        // Temporarily increase execution time for registration flow
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        } else {
            @ini_set('max_execution_time', '120');
        }

        Log::info('UserController:create called', ['email' => $request->input('email')]);

        $validation = Validator::make(
            $request->all(),
            [
                "password" => "required|min:8",
                "user_level" => "forbidden",
                "last_name" => "required|min:3",
                "first_name" => "required|min:3",
                "mobile_number" => "required|digits:11",
                "user_type" => ["required", Rule::in(["user", "rider"])],
                "email" => "required|unique:users|email:rfc",
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                $result = self::save($request);
                SettingController::save($result);
                UserWalletController::save($result);
            });
            Log::info('UserController:create completed DB transaction', ['email' => $request->input('email')]);

            try {
                $squadco = app(SquadcoService::class);

                if (!empty($request['first_name'])) {
                    $full_name = $request['first_name'] . ' ' . $request['last_name'];
                } elseif (!empty($request['name'])) {
                    $full_name = $request['name'];
                } else {
                    $full_name = $request->input('email');
                }

                $ref = 'SQVA' . time() . mt_rand(10000000, 9999999999);
                $payload = [
                    'account_name' => $full_name,
                    'email' => $request->input('email'),
                    'currency' => env('DEFAULT_CURRENCY', 'NGN'),
                    'reference' => $ref,
                    'phone' => $request->input('mobile_number') ?? $request->input('mobile_no') ?? $request->input('phone'),
                ];

                $resp = $squadco->createVirtualAccount($payload);

                // Map common response shapes to our StaticVirtualAccount format
                $account_number = null;
                $bank_name = null;
                $order_ref = $ref;

                if (is_array($resp) || is_object($resp)) {
                    $r = is_array($resp) ? $resp : (array) $resp;
                    if (!empty($r['data'])) {
                        $d = (array) $r['data'];
                        // common shapes
                        if (!empty($d['account_number'])) {
                            $account_number = $d['account_number'];
                        } elseif (!empty($d['account'][0]['account_number'])) {
                            $account_number = $d['account'][0]['account_number'];
                        } elseif (!empty($d['virtual_account']['account_number'])) {
                            $account_number = $d['virtual_account']['account_number'];
                        }

                        if (!empty($d['bank_name'])) {
                            $bank_name = $d['bank_name'];
                        } elseif (!empty($d['account'][0]['bank_name'])) {
                            $bank_name = $d['account'][0]['bank_name'];
                        } elseif (!empty($d['virtual_account']['bank'])) {
                            $bank_name = $d['virtual_account']['bank'];
                        }

                        if (!empty($d['reference'])) {
                            $order_ref = $d['reference'];
                        }
                    }

                    // fallback top-level
                    if (!$account_number) {
                        if (!empty($r['account_number'])) $account_number = $r['account_number'];
                        if (!empty($r['virtual_account']['account_number'])) $account_number = $r['virtual_account']['account_number'];
                        if (!empty($r['data']['account_number'])) $account_number = $r['data']['account_number'];
                    }
                }

                if (!empty($account_number)) {
                    $user = User::where('email', $request->input('email'))->first();
                    $svadata = [
                        'account_number' => $account_number,
                        'bank_name' => $bank_name ?? 'Unknown',
                        'txt_ref' => $ref,
                        'order_ref' => $order_ref ?? $ref,
                        'email' => $request->input('email'),
                        'user_id' => $user->id ?? null,
                    ];

                    \App\Http\Controllers\Funding\StaticVirtualAccountController::save($svadata);
                }
            } catch (\Throwable $exception) {
                Log::error('UserController:create virtual account (Squadco) failed', [
                    'email' => $request->input('email'),
                    'message' => $exception->getMessage(),
                    'payload' => $payload ?? null,
                    'response' => isset($resp) ? $resp : null,
                ]);
            }

            $login = AuthController::loginAtReg($request);
            Log::info('UserController:create login generated', ['email' => $request->input('email'), 'token_present' => !empty($login['token'])]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'User created successfully.',
                'token' => $login['token'],
                'user' => $login['user']
            ], 201);
        } catch (Exception $e) {
            Log::error('UserController:create exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. User not created successfully',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }


        //
    }

    /**
     * checks referral code
     *
     * if it exists
     *
     *
     */
    public static function checkReferralCode($referral_code)
    {
        $user = User::where('referral_code', $referral_code)->first();
        if (!empty($user->id)) {
            return true;
        } else {
            return false;
        }
    }



    /**
     * This method retrieves
     *
     *  user(s)
     *
     * @param $id
     */
    public static function get($id = null)
    {
        if (!empty($id)) {
            return $data = User::where(['id' => $id])->with(['userwallet'])->first();
        } elseif (empty($id)) {
            return $data = User::whereNotNull('id')->where('status', 'active')->with(['userwallet'])->orderBy('created_at', 'DESC')->paginate(30);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }

    /**
     * This method retrieves
     *
     * blocked user(s)
     *
     */

    public function blockedUsers()
    {
        return User::where('status', 'blocked')->orderBy('created_at', 'DESC')->paginate(25);
    }

    /**
     * This method retrieves
     *
     * blocked user(s)
     *
     */

    public static function checkIfAdmin($user_id)
    {
        $check = User::where(['status' => 'active'])->where('user_level_id', 7)
            ->first();
        if (!empty($check->id)) {
            return true;
        } else {
            abort();
        }
    }

    /**
     * This method deletes
     *
     *  user
     *
     * @param $id
     */
    public static function delete($id)
    {
        $delete = User::where('id', $id)->delete();
        if ($delete) {
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'

            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong User not deleted successfully'
            ]);
        }
    }

    /**
     * This method updates
     *
     *  a user
     *
     * @param $id
     */
    public static function update(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                "first_name" => "required|nullable",
                "last_name" => "required|nullable",
                "user_id" => "required"
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }
        $update = User::find($request['user_id']);

        if (!empty($request['name'])) {
            $update->first_name = $request['first_name'];
            $update->last_name = $request['last_name'];
            $update->mobile_number = $request['mobile_number'];
        }

        $saved = $update->save();

        if ($saved) {
            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'updated' => $update
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong User not updated successfully'
            ]);
        }
    }

    /**
     * This method updates the password
     *
     * for the users
     *
     */
    public static function changePassword(Request $request)
    {
        $data = $request->all();

        $validation = Validator::make(
            $request->all(),
            [
                "user_id" => "required|exists:users,id",
                "old_password" => "required|string",
                "new_password" => "required|min:6",
            ]
        );
        if ($validation->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }

        $confirm = User::where('id', $data['user_id'])->first();
        if (password_verify($data['old_password'], $confirm->password)) {
            $update = User::find($data['user_id']);
            $update->password = bcrypt($data['new_password']);
            $update->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Password updated successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, password not updated successfully. Please try again'
            ]);
        }
    }

    /**
     * This method verifies a channel
     *
     * @param $id
     *
     * @return response
     */
    public static function verifyUser($id)
    {
        if (!empty($id)) {
            $user = User::where('id', $id)->first();
            $user->profile_verified = 'profile_verified';
            $ok = $user->save();
            $data = [
                'user_id' => $user->id,
                'action_id' => $user->id,
                'action' => 'User',
                'comment' => 'Congrats. Your account has been verified.',
            ];
            //  NewNotificationController::save($data);
            if ($ok) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User account verified successfully',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong user account not verified successfully. Please try again',
                ]);
            }
        }
    }

    /**
     * Uploading image
     *
     * @param $request
     */

    public function uploadAvatar(Request $request)
    {
        $data = $request->all();
        $validation = Validator::make(
            $request->all(),
            [
                "avatar" => "required",
                "user_id" => "required"
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }

        if (!empty($request['avatar'])) {
            $user = User::find($request['user_id']);
            $user->avatar = ImageController::uploadAvatar($request);
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Profile image updated successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile image not updated successfully. Please try again.'
            ]);
        }
    }

    /**
     * searches users
     *
     * @return true
     *
     * @param $search term
     *
     */
    public static function searchUser(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'term' => 'required',

            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $result = User::where('email', $request['term'])->paginate(10);
        if (!empty($result)) {
            $totaluser = self::totalUser();
            $allusers = self::get();
            $user = Auth::user();
            return view('/dashboard/src/html/hotel/user')->with([
                'admin' => $user,
                'total_users' => $totaluser,
                'all_users' => $result,
            ]);
        } else {
            return view('/dashboard/src/html/hotel/error-page')->with([
                'msg' => 'User email ' . $request['term'] . ' not found. Make sure the email is spelt correctly and try again'
            ]);
        }
    }

    /**
     * changes admin email
     *
     *@param $request
     */
    public static function changeEmail($data)
    {

        try {
            DB::transaction(function () use ($data) {
                $code = mt_rand(1000, 9999);
                $confirm = User::where('id', $data['user_id'])->first();
                $update = User::find($data['user_id']);
                $update->email = $data['new_email'];
                $update->email_verified = Null;
                $update->save();
                EmailVerificationController::save($update->id, $code);
                // send verification mail
                //  $code = mt_rand(1000, 9999);
                /* return response()->json([
            'status' => 'success',
            'message' => 'Email updated successfully. Please note that you still have to verify this email']);*/
            });
        } catch (Exception $e) {
            /* return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, email not updated successfully. Please try again'
            ]);*/
        }
    }




    /**
     * gets the total number of
     * user signed up today
     *
     * @return response
     *
     */
    public static function totalUserToday()
    {
        $now = Carbon::today();
        return User::whereDate('created_at', $now)->count();
    }

    /**
     * gets the total number of
     * user signed up today, week, month, year
     *
     *
     * @return response
     *
     */
    public static function getTotalUsers($duration = null)
    {
        if (!empty($duration) && $duration == 1) {
            $duration = Carbon::today();
            $date_label = 'Today';
        } elseif ($duration == 2) {
            $now = Carbon::today();
            $duration = $now->subDays(2);
            $date_label = '48 hours ago';
        } elseif ($duration == 7) {
            $now = Carbon::today();
            $duration = $now->subDays(7);
            $date_label = '7 days ago';
        } elseif ($duration == 14) {
            $now = Carbon::today();
            $duration = $now->subDays(14);
            $date_label = '14 days ago';
        } elseif ($duration == 30) {
            $now = Carbon::today();
            $duration = $now->subDays(30);
            $date_label = '30 days ago';
        } elseif ($duration == 60) {
            $now = Carbon::today();
            $duration = $now->subDays(60);
            $date_label = '60 days ago';
        } elseif ($duration == 90) {
            $now = Carbon::today();
            $duration = $now->subDays(90);
            $date_label = '90 days ago';
        } elseif ($duration == 180) {
            $now = Carbon::today();
            $duration = $now->subDays(180);
            $date_label = '180 days ago';
        } elseif ($duration == 365) {
            $now = Carbon::today();
            $duration = $now->subDays(365);
            $date_label = '365 days ago';
        } elseif ($duration == null) {
            $date_label = 'All time';
            return User::whereNotNull('id')->count();
        }
        return User::whereDate('created_at', '>=', $duration)->count();
    }

    /**
     * gets the total number of
     * user signed up this week
     *
     * @return response
     *
     */
    public static function totalUserThisWeek()
    {
        $now = Carbon::now();
        $weekstart = $now->startOfWeek();
        return User::whereDate('created_at', '>=', $weekstart)->count();
    }


    /**
     * gets the total number of
     * user signed up this month
     *
     * @return response
     *
     */
    public static function totalUserThisMonth()
    {
        $now = Carbon::now();
        $monthstart = $now->startOfMonth();
        return User::whereDate('created_at', '>=', $monthstart)->count();
    }

    /**
     * gets the total number of
     * user signed up this year
     *
     * @return response
     *
     */
    public static function totalUserThisYear()
    {
        $now = Carbon::now();
        $yearstart = $now->startOfYear();
        return User::whereDate('created_at', '>=', $yearstart)->count();
    }

    /**
     * gets the total number of
     * all users signed on the platform
     *
     * @return response
     *
     */
    public static function totalUser()
    {
        return User::whereNotNull('id')->count();
    }

    /**
     * Records last login
     *
     * @return response
     *
     */
    public static function updateLastLogin($user_id)
    {
        $user = User::find($user_id);
        $user->last_login_date = Carbon::now();
        $user->save();
    }

    /**
     * This method unsubscribes a
     *
     * user
     * @param email
     * @return a response
     */
    public function unsubscribe($request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',

            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }
        $user = User::where('email', $request['email'])->first();
        if (!empty($user->email)) {
            $user->receive_emails = 'unsubscribe';
            $user->save();
        }
    }

    /**
     * This method gets a
     *
     * userID
     *
     * @param $referral_code
     * @return a response
     */
    public static function getUserId($referral_code)
    {
        $user = User::where('referral_code', $referral_code)->first();
        if (!empty($user->id)) {
            return $user->id;
        } else {
            return NULL;
        }
    }


    /**
     * This method gets a
     *
     * user details
     *
     *
     * @return a response
     */
    public static function userDetails($user_id)
    {
        $total_engagements = Post::where('user_id', $user_id)->sum('total_engagements');
        $user = User::where('id', $user_id)->withCount('post', 'userChannel')->first();
        return ['total_engagement' => $total_engagements, 'users' => $user];
    }


    /**
     * Sets a password
     *
     */
    public static function setPassword($email, $password)
    {
        $user = User::where('email', $email)->first();
        $user->password = Hash::make($password);
        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Password set successfully'
        ]);
    }


    /**
     * generates a unique referral code
     * for every user
     *
     */
    public static function generateReferralCode()
    {
        do {
            $code = time() - mt_rand(100000000, 999999999);
            $random = strtoupper(Str::random(2));
            $new_code = $random . '-' . $code;
            $exists = User::where('referral_code', $new_code)->exists();
        } while ($exists);

        return $new_code;
    }



    /**
     * get my referral code
     *
     * @param $user_id
     *
     */
    public static function myReferralCode($user_id)
    {
        $myReferred = ReferralController::myReferred($user_id);
        $user = User::where('id', $user_id)->first();
        if (!empty($user->referral_code)) {
            return [$user->referral_code, $myReferred];
        } else {
            $code = self::generateReferralCode();
            $user->referral_code = $code;
            $user->save();
        }
        return [$user->referral_code, $myReferred];
    }
}
