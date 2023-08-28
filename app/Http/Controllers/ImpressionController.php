<?php

namespace App\Http\Controllers;

use App\Models\Impression;
use App\Models\Post;
use Illuminate\Http\Request;

class ImpressionController extends Controller
{
    // Add impression on  post
    /**
     * @OA\POST(
     * path="/api/impression/{id}",
     * operationId="Add impression oon a post by post id",
     * tags={"Add impression oon a post by post id"},
     * summary="Add impression oon a post by post id",
     * description="Add impression oon a post by post id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post ", type="number"),
     *            ),
     *        ),
     *    ),
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
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Not authenticated"),
     * )
     */
    public function impression($post)
    {
        try {
            $chk = Post::whereId($post)->exists();
            if (!$chk) {
                return $this->statusCode(404, "Post not found");
            }

            $data['impression'] = Impression::create([
                "user_id" => request()->user('sanctum')->id ?? null,
                "post_id" => $post,
                "impression" => true,
            ]);

            return $this->statusCode(200, 'You added your impression on this post', ['data' => $data]);
        } catch (\Throwable $th) {
            return $this->statusCode(401, "We couldn't process your request, please try again.");
        }
    }
}
