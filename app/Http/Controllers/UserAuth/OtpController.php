<?php

namespace App\Http\Controllers\UserAuth;

use App\Http\Controllers\Controller;
use App\Jobs\SendOTPJobs;
use App\Mail\OtpMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    // for single users
    public function sendOTP(User $user)
    {
        // check of OTP is null or not
        if ($user->otp == null) {
            $signCo = new SignupController();
            $otp = Hash::make($signCo->randomString());
            $user->sendOTP($otp);
        }

        Mail::to($user->email)->Send(new OtpMail($user));
        return $this->statusCode(300, 'Account created successfully, kindly confirm the OTP sent in your email to activate your account. Check spam if not seen in your inbox');
    }

    // for Bulk users
    public function sendMassOTP(array $users)
    {
        // check of OTP is null or not
        foreach ($users as $user) {
            dispatch(new SendOTPJobs($user));
        }

        return $this->statusCode(300, 'Accounts created successfully, kindly confirm the OTPs sent in your emails to activate your account. Check spam if not seen in your inbox');
    }

    public function sendResetPasswordOTP(User $user)
    {
        $signCo = new SignupController();
        $otp = Hash::make($signCo->randomString());
        $user->sendOTP($otp, false);
        Mail::to($user->email)->Send(new PasswordResetMail($user));
        return $this->statusCode(300, 'Password reset link sent in your email. Check spam if not seen in your inbox', ['token' => $user->token]);
    }

    public function verifyOTP(Request $request, ?string $pg = '', ?string $msg = '')
    {
        // check if OTP belongs to email
        $chk = User::whereToken(strip_tags(htmlspecialchars(trim($request->token))))->whereOtp(strip_tags(htmlspecialchars(trim($request->otp))))->count();

        if ($chk > 0) {
            $user = User::whereToken(strip_tags(htmlspecialchars(trim($request->token))))->whereOtp(strip_tags(htmlspecialchars(trim($request->otp))))->first();
            $pg == '' ? $user->verifyUser() : $user->resetCode();
            if ($user->welcome_sent == 0) {
                Mail::to($user->email)->Send(new WelcomeMail($user));
                $user->WelcomeMailSent();
            }
            return $msg == '' ? redirect(env('BASE_URL') . '/login') : redirect(env('BASE_URL') . '/resetpassword/?token=' . $user->token);
        } else {
            return view('error.error', ['msg' => 'OTP invalid']);
        }
    }

    /**
     * @OA\Post(
     * path="/api/otp/resend/{token}",
     * operationId="Resend Account Activation OTP",
     * tags={"Resend Account Activation OTP"},
     * summary="Resend Account Activation OTP",
     * description="Resend Account Activation OTP here",
     *      @OA\Response(
     *          response=300,
     *          description="OTP sent successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */
    public function resendOTP($token)
    {
        $chk = User::whereToken($token)->count();
        if ($chk > 0) {
            $user = User::whereToken(trim($token))->first();
            return $this->sendOTP($user);
        }
        return $this->statusCode(404, "invalid token");
    }

    /**
     * @OA\Post(
     * path="/api/password/resend/{token}",
     * operationId="Resend Password Reset OTP",
     * tags={"Resend Password Reset OTP"},
     * summary="Resend Password Reset OTP",
     * description="Resend Password Reset OTP here",
     *      @OA\Response(
     *          response=300,
     *          description="OTP sent successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=499, description="Account disabled"),
     *      @OA\Response(response=500, description="Form disabled. Wait for 10 minutes"),
     * )
     */
    public function resendPasswordOTP($token)
    {
        $chk = User::whereToken($token)->count();
        if ($chk > 0) {
            $user = User::whereToken(trim($token))->first();
            return $this->sendResetPasswordOTP($user);
        }
        return $this->statusCode(404, "invalid token");
    }

    /**
     * @OA\Get(
     * path="/api/password/reset",
     * summary="Verify Password Reset link",
     * description="Verify Password Reset link",
     * tags={"OTP Password Reset Verification"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="OTP successful, proceed to change your password",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="OTP invalid",
     *          @OA\JsonContent()
     *       ),
     * )
     */
    public function verifyPasswordOtp(Request $request)
    {
        return $this->verifyOTP($request, 'pass', 'OTP successful, proceed to change your password');
    }
}
