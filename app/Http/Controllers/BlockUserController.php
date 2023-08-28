<?php

namespace App\Http\Controllers;

use App\Models\BlockUser;
use App\Models\User;
use Illuminate\Http\Request;

class BlockUserController extends Controller
{
    /**
     * @OA\POST(
     * path="/api/mute/user/{user_id}",
     * operationId="Mute / Block User",
     * tags={"Mute / Block Users"},
     * summary="Mute / Block Users",
     * description="User Mute / Block Users",
     *      @OA\Response(
     *          response=201,
     *          description="User muted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="User muted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function BlockUser($user_id)
    {
        try {
            // check if userid belongs to login user
            if ($user_id == auth()->id()) {
                return $this->statusCode(422, 'You cannot block yourself');
            }
            // check if id exits
            $chk = User::whereId($user_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No user found');
            }

            // check if user is already blocked
            $user = User::find(auth()->id());
            $chkIfBlockedAlready = $user->blockedUsers()->whereBlocked_user($user_id)->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedUsers()->whereBlocked_user($user_id)->first();
                return $this->statusCode(200, 'User already blocked. No operation was performed', ['blocked' => $blocked]);
            }

            // else block user
            $user->blockedUsers()->create([
                'blocked_user' => $user_id
            ]);

            $blocked = $user->blockedUsers()->whereBlocked_user($user_id)->first();

            return $this->statusCode(200, 'User muted successfully', ['blocked' => $blocked]);
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    /**
     * @OA\POST(
     * path="/api/unmute/user/{user_id}",
     * operationId="Unmute / Unblock User",
     * tags={"Unmute / Unblock Users"},
     * summary="Unmute / Unblock Users",
     * description="User Unmute / Unblock Users",
     *      @OA\Response(
     *          response=201,
     *          description="User Unmuted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="User Unmuted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function UnblockUser($user_id)
    {
        try {
            // check if userid belongs to login user
            if ($user_id == auth()->id()) {
                return $this->statusCode(422, 'You cannot operate on yourself');
            }
            // check if id exits
            $chk = User::whereId($user_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No user found');
            }

            // check if user is already blocked
            $user = User::find(auth()->id());
            $chkIfBlockedAlready = $user->blockedUsers()->whereBlocked_user($user_id)->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedUsers()->whereBlocked_user($user_id)->first();
                $blocked->delete();
                return $this->statusCode(200, 'User unmuted successfully');
            }

            return $this->statusCode(404, 'No user found');
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

     /**
     * @OA\Get(
     * path="/api/mute/all",
     * operationId="Current login user muted accounts",
     * tags={"Current login user muted accounts"},
     * summary="Current login user muted accounts",
     * description="Current login user muted accounts",
     *      @OA\Response(
     *          response=200,
     *          description="Request successful.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessed entity"),
     * )
     */
    public function myBlockedUsers()
    {
        try {

            $user = User::find(auth()->id());
            $chkIfBlockedAlready = $user->blockedUsers()->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedUsers()
                ->with('user.profile')
                ->paginate(30);
                return $this->statusCode(200, 'Record available', ['blocked' => $blocked]);
            }

            return $this->statusCode(404, 'No record available');
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }
}
