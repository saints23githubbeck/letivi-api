<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SharePostCount;
use Illuminate\Http\Request;

class SharePostCountController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/share/post/{post_id}",
     * operationId="Increase Share Post Count",
     * tags={"EIncrease Share Post Count"},
     * summary="Increase Share Post Count",
     * description="Increase Share Post Count here",
     *      @OA\Response(
     *          response=201,
     *          description="Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function increaseShareCount($post_id)
    {
        $chkPost = Post::whereId($post_id)->count();
        if ($chkPost > 0) {
            $post = Post::find($post_id);
            $chkShareCount = $post->postShares()->count();
            if ($chkShareCount > 0) {
                $oldValue = $post->postShares()->first();
                // return $oldValue;
                $newVal = 1 + $oldValue->share_count;
                $post->postShares()->update([
                    'share_count' => $newVal
                ]);
                $ps = $post->postShares()->first();
                return  $this->statusCode(200, 'Share count recorded', ['data' => $ps]);
            } else {
                $post->postShares()->create([]);
                $ps = $post->postShares()->first();
                return  $this->statusCode(200, 'Share count recorded', ['data' => $ps]);
            }
        } else {
            return $this->statusCode(404, 'No post found');
        }
    }
}
