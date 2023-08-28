<?php

namespace App\Http\Controllers;

use App\Models\BlockPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class BlockPostController extends Controller
{
    /**
     * @OA\POST(
     * path="/api/mute/post/{post_id}",
     * operationId="Mute / Block Post",
     * tags={"Mute / Block Post"},
     * summary="Mute / Block Post",
     * description="Post Mute / Block Post",
     *      @OA\Response(
     *          response=201,
     *          description="Post muted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Post muted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function BlockPost($post_id)
    {
        try {
            $user = User::find(auth()->id());
            // check if post_id belongs to login user
            $chk = $user->posts()->whereId($post_id)->count();
            if ($chk > 0) {
                return $this->statusCode(422, 'You cannot block own post');
            }
            // check if id exits
            $chk = Post::whereId($post_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No post found');
            }

            // check if post is already blocked
            $chkIfBlockedAlready = $user->blockedPosts()->whereId($post_id)->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedPosts()->whereId($post_id)->first();
                return $this->statusCode(200, 'Post already blocked. No operation was performed', ['blocked' => $blocked]);
            }

            // else block user
            $user->blockedPosts()->create([
                'post_id' => $post_id
            ]);

            $blocked = $user->blockedPosts()->whereId($post_id)->first();

            return $this->statusCode(200, 'Post muted successfully', ['blocked' => $blocked]);
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    /**
     * @OA\POST(
     * path="/api/unmute/user/{post_id}",
     * operationId="Unmute / Unblock Post",
     * tags={"Unmute / Unblock Posts"},
     * summary="Unmute / Unblock Posts",
     * description="Post Unmute / Unblock Posts",
     *      @OA\Response(
     *          response=201,
     *          description="Post Unmuted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Post Unmuted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=422, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function UnblockPost($post_id)
    {
        try {
            $user = User::find(auth()->id());
            // check if post_id belongs to login user
            $chk = $user->posts()->whereId($post_id)->count();
            if ($chk > 0) {
                return $this->statusCode(422, 'You cannot block own post');
            }
            // check if id exits
            $chk = Post::whereId($post_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No Post found');
            }

            // check if user is already blocked
            $user = Post::find(auth()->id());
            $chkIfBlockedAlready = $user->blockedPosts()->whereId($post_id)->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedPosts()->whereId($post_id)->first();
                $blocked->delete();
                return $this->statusCode(200, 'Post unmuted successfully');
            }

            return $this->statusCode(404, 'No Post found');
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

     /**
     * @OA\Get(
     * path="/api/mute/post/all",
     * operationId="Current login user muted posts",
     * tags={"Current login user muted posts"},
     * summary="Current login user muted posts",
     * description="Current login user muted posts",
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
    public function myBlockedPosts()
    {
        try {

            $user = User::find(auth()->id());
            $chkIfBlockedAlready = $user->blockedPosts()->count();
            if ($chkIfBlockedAlready > 0) {
                $blocked = $user->blockedPosts()
                ->with('post.media')
                ->paginate(30);
                return $this->statusCode(200, 'Record available', ['blocked' => $blocked]);
            }

            return $this->statusCode(404, 'No record available');
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }
}
