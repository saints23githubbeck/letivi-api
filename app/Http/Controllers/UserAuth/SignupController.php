<?php

namespace App\Http\Controllers\UserAuth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CountryController;
use App\Mail\WelcomeMail;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserFollowing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class SignupController extends Controller
{
    private $storage_path;
    private $path;

    public function __construct()
    {
        $this->storage_path = public_path('profile/');
        $this->path = 'profile/';
    }


    /**
     * return String w258272033737b6415F
     */
    public function randomString(): string
    {
        // generate a pin based on 2 * 7 digits + a random character
        $pin = mt_rand(1000000, 9999999)
            . mt_rand(1000000, 9999999)
            . Str::random(5);
        // shuffle the result
        $string = str_shuffle($pin);
        return $string;
    }

    private function generateUniqueProfileInviteLink()
    {
        $link = $string = '';
        do {
            $string = $this->randomString();
            $chk = Profile::whereToken($string)->count();
        } while ($chk > 0);
        $baseUrl = request()->host();
        $link = $baseUrl . '/profile/' . $string;
        return ['link' => $link, 'token' => $string];
    }

    /**
     * @OA\Post(
     * path="/api/users",
     * operationId="Register",
     * tags={"User Registration"},
     * summary="User Register",
     * description="User Register here. Kindly ensure that date is formatted as 'Y-m-d'. Assign 'apple' or 'google' to 'login provider' field if user is performing social login",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"country_name","profession", "date_of_birth", "gender", "private"},
     *               @OA\Property(property="first_name", type="string"),
     *               @OA\Property(property="last_name", type="string"),
     *               @OA\Property(property="email", type="email"),
     *               @OA\Property(property="date_of_birth", type="string"),
     *               @OA\Property(property="gender", type="string"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="confirm_password", type="password"),
     *               @OA\Property(property="country_name", type="string"),
     *               @OA\Property(property="private", type="boolean"),
     *               @OA\Property(property="profession", type="string"),
     *               @OA\Property(property="industry", type="integer"),
     *               @OA\Property(property="other_industry", type="string"),
     *               @OA\Property(property="login_provider", type="string"),
     *               @OA\Property(property="google_auth_token", type="string"),
     *               @OA\Property(property="apple_auth_token", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="User Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="User Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=300,
     *          description="Confirm Email OTP sent",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=210, description="Error in input fields"),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */



    public function create(Request $request, ?bool $byAdmin = false)
    {

        $otpController = new OtpController();
        $socialAuth = new SocialAuthController;
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'gender' => ['required', 'string'],
                    'date_of_birth' => ['required'],
                    'private' => ['required'],
                    'profession' => ['required', 'string'],
                    'country_name' => ['required', 'string'],

                    'industry' => ['sometimes', 'nullable', 'integer'],
                    'other_industry' => ['sometimes', 'nullable', 'string'],
                    'email' => ['sometimes', 'required', 'email', 'indisposable'],
                    'first_name' => ['sometimes', 'required', 'string'],
                    'last_name' => ['sometimes', 'required', 'string'],
                    'login_provider' => ['sometimes', 'nullable', 'string'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if correct date
            try {
                Carbon::parse($request->date_of_birth);
            } catch (\Exception $e) {
                return $this->statusCode(422, "Invalid date format. Date must be formatted as [Y-m-d thus 2xxx-01-25 or d-m-Y thus 21-08-2xxx]");
            }

            // check if social login
            if ($request->has('login_provider') && $request->filled('login_provider')) {
                $loginProvider = trim(strtolower($request->login_provider));
                switch ($loginProvider) {
                    case 'google':
                        $validate = Validator::make(
                            $request->all(),
                            [
                                'google_auth_token' => ['required']
                            ]
                        );

                        if ($validate->fails()) {
                            return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
                        }
                        $response = $socialAuth->getDataFromGoogle($request->google_auth_token);

                        if ($response['status'] == 'error') {
                            return $this->statusCode($response['code'], $response['msg'], ['error' => $response['data']]);
                        }
                        // check if email already exist
                        if (User::whereEmail($response['data']['email'])->count() > 0) {
                            return $this->statusCode(404, 'Email already taken. Use a different email or go to Login page to login');
                        } else {
                            // create user account
                            $customField = [
                                'email' => $response['data']['email'],
                                'first_name' => $response['data']['given_name'],
                                'last_name' => $response['data']['family_name'],
                            ];

                            $myArr = $this->newUser($request, false, $customField);
                            // return $myArr;
                            $msg = 'Account created successfully';

                            return is_array($myArr) && count($myArr) ? $this->socialLogin($myArr['user'], $msg) : $this->statusCode(500, "Error occured while processing your request");
                        }
                        break;
                    case 'apple':
                        $validate = Validator::make(
                            $request->all(),
                            [
                                'apple_auth_token' => ['required']
                            ]
                        );

                        if ($validate->fails()) {
                            return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
                        }
                        // return $this->statusCode(422, 'Apple Signup Not supported yet');
                        $provider = 'apple';
                        $token = $request->apple_auth_token;

                        $socialUser = Socialite::driver($provider)->userFromToken($token);

                        $sub = $socialUser->attributes['id'] ?? 'xxx';
                        $email = $socialUser->attributes['email'] ?? $request->email;

                        $chk = User::whereApple_token($sub)->count();
                        if ($chk > 0) {
                            return $this->statusCode(404, 'Email already taken. Use a different email or go to Login page to login');
                        }

                        $chk = User::whereEmail(trim($email))->count();
                        if ($chk > 0) {
                            return $this->statusCode(404, 'Email already taken. Use a different email or go to Login page to login');
                        }

                        // else create new user
                        // create user account
                        $customField = [
                            'id' => $sub,
                            'email' => $email
                        ];

                        $myArr = $this->newUser($request, false, $customField);
                        // return $myArr;
                        $msg = 'Account created successfully';

                        return is_array($myArr) && count($myArr) ? $this->socialLogin($myArr['user'], $msg) : $this->statusCode(500, "Error occured while processing your request");

                        break;
                    default:
                        return $this->statusCode(422, 'Social Auth Provider not supported');
                        break;
                }
            }

            // run this if not social signup

            // check if email already exist
            if (User::whereEmail(trim(strtolower($request->email)))->count() > 0) {
                // check if account is verified

                $chk = User::whereNotNull('email_verified_at')->whereEmail(trim(strtolower($request->email)))->count();

                if ($chk > 0) {
                    return $this->statusCode(404, 'Email already taken. Use a different email');
                } else {
                    $user = User::whereEmail(trim(strtolower($request->email)))->first();
                    $data = $this->updateUser($request, $user);
                    // return $data;
                    return is_array($data) && count($data) > 0 ? $otpController->sendOTP($data['user']) : throw new Exception("Error occured while processing your request", 1);
                }
            }


            $validate = Validator::make(
                $request->all(),
                [
                    'password' => ['required', 'max:50', 'min:7'],
                    'confirm_password' => ['required', 'max:50', 'min:7']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            if ($request->password != $request->confirm_password) {
                return $this->statusCode(401, 'Passwords mismatch');
            }

            $user = $this->newUser($request, true);
            // return $user;
            if (is_array($user) && count($user) > 0) {
                if ($byAdmin) {
                    // verify the email and mail sent
                    $dd = User::find($user['user']->id);
                    $dd->WelcomeMailSent();
                    $dd->verifyUser();

                    $usr = User::find($user['user']->id);

                    $data = [];
                    // Keys to exclude
                    $excludedKeys = ['user'];
                    $data = array_diff_key($user, array_flip($excludedKeys));

                    $data['user'] = $usr;

                    return $this->statusCode(200, 'Account created successfully', ['data' => $data]);

                } else {
                    return $otpController->sendOTP($user['user']);
                }
            } else {
                throw new Exception("Error occured while processing your request", 1);
            }
        } catch (\Throwable $th) {
            return $this->statusCode(401, $th->getMessage());
            // return $this->statusCode(401, "Error occured while processing your request");
        }
    }

    // log social user in
    public function socialLogin(User $user, $msg = null)
    {
        $data = [];
        $user->update([
            'token' => $this->makeToken()
        ]);
        Auth::login($user);
        $data['user'] = User::select('id', 'first_name', 'last_name', 'email', 'token', 'other_industry')->with(
            'profession:id,user_id,profession',
            'profile:id,user_id,picture,country'
        )->whereId($user->id)->first();
        $data['token'] = $user->createToken('main')->plainTextToken;
        $data['totalFollowers'] = UserFollowing::whereFollowing_id($user->id)->count();
        $data['totalFollowing'] = UserFollowing::whereUser_id($user->id)->count();;
        $data['totalPhotos'] = $user->posts()->with('medias')->whereType('image')->count();
        $data['totalVideos'] = $user->posts()->with('medias')->whereType('video')->count();
        $data['totalPosts'] = $user->posts()->count();
        $data['totalInvitedFriends'] = $user->invites()->count();


        return $this->statusCode(200, $msg ?? 'Login successful', $data);
    }

    private function makeToken(): string
    {
        $uniqueToken = '';
        do {
            $uniqueToken = $this->randomString();
            $cnt = User::whereToken($uniqueToken)->count();
        } while ($cnt > 0);
        return $uniqueToken;
    }

    private function newUser(Request $request, $hasPassword = false, $customField = null)
    {
        $data = [];
        try {
            $private = '';
            if ($request->private == true) {

                $private = 1;
            } elseif ($request->private == false) {
                $private = 0;
            }
            $linkArr = $this->generateUniqueProfileInviteLink();
            $status = DB::transaction(function () use ($request, $hasPassword, $data, $private, $linkArr, $customField) {
                $dateOfBirth = Carbon::parse($request->date_of_birth)->format('Y-m-d');

                $user = User::create([
                    'email' => $customField == null ? trim(strtolower($request->email)) : $customField['email'],
                    'first_name' => $customField == null || !isset($customField['first_name']) ? trim($request->first_name) : $customField['first_name'],
                    'last_name' => $customField == null || !isset($customField['last_name']) ? trim($request->last_name) : $customField['last_name'],
                    'gender' => trim(strtolower($request->gender)),
                    'password' => $hasPassword == true ? Hash::make($request->password) : Hash::make($this->randomString()),
                    'date_of_birth' => $dateOfBirth,
                    'private' => $private,
                    'industry_id' => $request->filled('industry') ? $request->industry : null,
                    'other_industry' => $request->filled('industry') ? null : $request->other_industry,
                    'email_verified_at' => $hasPassword == null ? Carbon::now() : null,
                    'token' => $this->makeToken(),
                    'apple_token' => $customField == null || !isset($customField['id']) ? null : $customField['id'],
                    'welcome_sent' => $customField == null ? false : true,
                ]);
                // dd($request);

                // This function create default profile, the first time user register
                $data['profile'] = $user->profile()->create([
                    'country' => trim(ucwords($request->country_name)),
                    'phone_number' => $request->phone_numeber,
                    'invite_link' => $linkArr['link'],
                    'token' => $linkArr['token'],
                    'bio' => $request->bio
                ]);

                // This function create default profile, the first time user register
                $data['profession'] = $user->profession()->create([
                    'profession' => trim($request->profession),
                    'work_experience' => $request->work_experience,
                    'linkedin' => $request->linkedin,
                    'facebook' => $request->facebook,
                    'youtube' => $request->youtube,
                    'twitter' => $request->twitter,
                    'instagram' => $request->instagram,
                    'website' => $request->website,
                ]);
                $data['user'] = $user;
                $countryCont = new CountryController();
                $countryCont->storeCountry($user->id);

                // send congratulatory mail
                try {
                    $customField != null ? Mail::to($user->email)->Send(new WelcomeMail($user)) : '';
                } catch (\Throwable $e) {
                    // throw new Exception("Problem with sending mail", 1);
                    return $data;
                }
                return $data;
            });

            return $status;
        } catch (\Throwable $e) {
            // return $e->getMessage();
            return [];
        }
    }

    // this function is only for when user hasn't verified account yet
    private function updateUser(Request $request, User $user)
    {
        $data = [];
        try {
            $private = '';
            if ($request->private == true) {

                $private = 1;
            } else {
                $private = 0;
            }
            $status = DB::transaction(function () use ($request, $user, $data, $private) {
                $dateOfBirth = Carbon::parse($request->date_of_birth)->format('Y-m-d');
                $user->update([
                    'email' => trim(strtolower($request->email)),
                    'first_name' => trim($request->first_name),
                    'last_name' => trim($request->last_name),
                    'gender' => trim(strtolower($request->gender)),
                    'password' => Hash::make($request->password),
                    'date_of_birth' => $dateOfBirth,
                    'private' => $private,
                    'industry_id' => $request->filled('industry') ? $request->industry : null,
                    'other_industry' => $request->filled('industry') ? null : $request->other_industry,
                    'email_verified_at' => null
                ]);

                // This function create default profile, the first time user register
                $data['profile'] = $user->profile()->update([
                    'country' => trim(ucwords($request->country_name)),
                    'phone_number' => $request->phone_numeber,
                    'invite_link' => $request->invite_link,
                    'bio' => $request->bio
                ]);

                // This function create default profile, the first time user register
                $data['profession'] = $user->profession()->update([
                    'profession' => trim(ucwords($request->profession)),
                    'work_experience' => $request->work_experience,
                    'linkedin' => $request->linkedin,
                    'facebook' => $request->facebook,
                    'youtube' => $request->youtube,
                    'twitter' => $request->twitter,
                    'instagram' => $request->instagram,
                    'website' => $request->website
                ]);

                $data['user'] = $user;

                $countryCont = new CountryController();
                $countryCont->storeCountry($user->id);
                return $data;
            });
            return $status;
        } catch (\Throwable $e) {
            // return $e->getMessage();
            return [];
        }
    }

    /**
     * @OA\POST(
     * path="/api/mails",
     * operationId="Validate user email",
     * tags={"Validate user email"},
     * summary="Validate user email",
     * description="Validate user email here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email"},
     *               @OA\Property(property="email", type="email"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Email exist",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Email exist",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=300,
     *          description=" Email exist",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=210, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function mailValidation(Request $request)
    {

        $chk =  User::whereEmail(trim(strtolower($request->email)))->count();

        if ($chk == 0) {
            return $this->statusCode(200, 'Email not found');
        } else {
            return $this->statusCode(400, 'Email already taken. Use a different email');
        }
    }
}
