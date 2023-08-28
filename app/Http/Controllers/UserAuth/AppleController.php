<?php

namespace App\Http\Controllers\UserAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AppleController extends Controller
{
    /**
     * @OA\POST(
     * path="/api/apple/login",
     * operationId="Login using Apple",
     * tags={"Login using Apple"},
     * summary="Login using Apple",
     * description="Login using Apple here",
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
    public function appleLogin(Request $request)
    {
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

            $provider = 'apple';
            $token = $request->token;

            $socialUser = Socialite::driver($provider)->userFromToken($token);

            $sub = $socialUser->attributes['id'] ?? 'xxx';

            $chk = User::whereApple_token($sub)->count();

            if ($chk > 0) {
                $signUpCont = new SignupController;
                $user = User::whereApple_token($sub)->first();
                return $signUpCont->socialLogin($user);
            }
            return $this->statusCode(404, 'New email detected.');
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    public function appleCallback(Request $request)
    {
        return $request;
    }
}
