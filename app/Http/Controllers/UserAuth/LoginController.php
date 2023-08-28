<?php

namespace App\Http\Controllers\UserAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


/**
 * @OA\Post(
 * path="/api/users/login",
 * operationId="User Login",
 * tags={"User Login"},
 * summary="User Login",
 * description="User  Login here",
 *     @OA\RequestBody(
 *         @OA\JsonContent(),
 *         @OA\MediaType(
 *            mediaType="multipart/form-data",
 *            @OA\Schema(
 *               type="object",
 *               required={"email","password"},
 *               @OA\Property(property="email", type="string"),
 *               @OA\Property(property="password", type="password"),
 *            ),
 *        ),
 *    ),
 *      @OA\Response(
 *          response=200,
 *          description="Login   Successfully",
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
 *      @OA\Response(response=400, description="Bad request"),
 *      @OA\Response(response=401, description="Error occured while processing request"),
 *      @OA\Response(response=403, description="Error in input fields"),
 *      @OA\Response(response=404, description="Resource Not Found"),
 *      @OA\Response(response=499, description="Account disabled"),
 *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
 * )
 */

class LoginController extends Controller
{
    public function login(Request $request)
    {

        $otpController = new OtpController();
        $signUpCont = new SignupController();

        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => ['required', 'email', 'indisposable'],
                    'password' => ['sometimes', 'required'],
                    'login_provider' => ['sometimes', 'nullable', 'string']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
        } catch (\Throwable $e) {
            return $this->statusCode(404, $e->getMessage());
        }

        try {
            $this->checkTooManyFailedAttempts();
        } catch (Exception $e) {
            if (Cache::has('invalidEmail')) {
                return $this->statusCode(500, 'Too many login attempts on invalid credentials. Login will be disabled for 10 minutes');
            }

            // run this code only once
            $sent = Cache::get('isSent');
            if (session('isLocked') === 1 && $sent === 0) {
                // update user column and send mail once login attempt is due
                $chk = User::whereEmail($request->email)->count();
                if ($chk > 0) {
                    $user = User::whereEmail($request->email)->first();
                    $otpController->sendOTP($user);
                    Cache::put('isSent', 1);
                    Cache::has('invalidEmail') ? Cache::forget('invalidEmail') : '';
                }
            }
            // end run
            return $this->statusCode(300, 'Too many login attempts detected. Account has been locked temporarily. Confirm mail otp to recover account.');
        }

        $isAvailable = User::whereEmail($request->email)->count();
        if ($isAvailable > 0) {
            // check if social login
            $user = User::whereEmail(trim(strtolower($request->email)))->first();
            $loginProvider = trim(strtolower($request->login_provider));
            if ($loginProvider == 'apple' || $loginProvider == 'google') {
                // check if account is verified
                $chk = User::whereNotNull('email_verified_at')->whereEmail(trim(strtolower($request->email)))->count();
                return $chk > 0 ? $signUpCont->socialLogin($user) : $otpController->sendOTP($user);
            } else {
                // check if account is verified
                $chk = User::whereNotNull('email_verified_at')->whereEmail(trim(strtolower($request->email)))->count();
                if ($chk > 0) {
                    // check Password
                    if (Hash::check($request->password, $user->password)) {
                        return $signUpCont->socialLogin($user);
                    } else {
                        RateLimiter::hit($this->throttleKey(), 600);
                        Cache::has('invalidEmail') ? Cache::forget('invalidEmail') : '';
                        return $this->statusCode(401, "Password incorrect");
                    }
                } else {
                    RateLimiter::hit($this->throttleKey(), 600);
                    Cache::has('invalidEmail') ? Cache::forget('invalidEmail') : '';
                    return $otpController->sendOTP($user);
                }

                // check if account is verified
                RateLimiter::clear($this->throttleKey());
                $chk = User::whereNotNull('email_verified_at')->whereEmail(trim(strtolower($request->email)))->count();
                return $chk > 0 ? $signUpCont->socialLogin(User::whereEmail(trim(strtolower($request->email)))->first()) : $otpController->sendOTP($user);
            }
        } else {
            RateLimiter::hit($this->throttleKey(), 600);
            Cache::put('invalidEmail', $request->email);
            return $this->statusCode(404, 'Email address does not exist');
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower(request('email')) . '|' . request()->ip();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     */

    public function checkTooManyFailedAttempts()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            Cache::put('isSent', 0);
            return;
        }
        session(['isLocked' => 1]);
        throw new Exception('IP address banned. Too many login attempts.');
    }

    /**
     * @OA\POST(
     * path="/api/googlelogin",
     * operationId="Login using Google",
     * tags={"Login using Google"},
     * summary="Login using Google",
     * description="Login using Google here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"token"},
     *               @OA\Property(property="token", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successful",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successful",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Email does not exist"),
     *      @OA\Response(response=401, description="Invalid Token"),
     *      @OA\Response(response=210, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function googleLogin(Request $request)
    {
        $signUpCont = new SignupController;
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'token' => ['required'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(210, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $socialAuth = new SocialAuthController;

            $res = $socialAuth->getDataFromGoogle($request->token);
            if ($res['status'] == 'error') {
                return $this->statusCode($res['code'], $res['msg'], $res['data']);
            }

            $response = $res['data'];
            $chkMail = User::whereEmail($response['email'])->count();

            if ($chkMail == 0) {
                return $this->statusCode(404, 'Account does not exist');
            } else {
                $user = User::whereEmail($response['email'])->first();
                return $signUpCont->socialLogin($user);
            }
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, 'Error occured while processing your request');
        }
    }
}
