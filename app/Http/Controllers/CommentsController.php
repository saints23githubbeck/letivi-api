<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Impression;
use App\Models\Post;
use App\Models\Reply;
use App\Models\View;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    // Comment on  post

    /**
     * @OA\Post(
     * path="/api/comments/{id}",
     * operationId="Comment on  post by post id",
     * tags={"Comment on post by post id"},
     * summary="Comment on post by post id",
     * description="Comment on post by post id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post", type="number"),
     *               @OA\Property(property="comment", description="user comment", type="string"),
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

    public function comment(Request $request,Post $post)
    {

        try {
            if (request()->user('sanctum')) {


                $post_id = Post::whereId($post->id)->count();

                if ($post_id > 0) {

                    $data['comment'] = Comment::create([
                        "comment" => $request->comment,
                        "user_id" => auth()->id(),
                        "post_id" => $post->id,
                    ]);
                }

                return $this->statusCode(200, 'Request  successful',['data'=> $data]);
            } else {
                return $this->statusCode(407, 'Please login');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }




    /**
     * @OA\Get(
     * path="/api/comments/posts/{id}",
     * summary="Fetch Comments On Single post",
     * description="Fetch Comments On Single post",
     * tags={"Fetch Comments On Single post (accerpt cast id )"},
     * security={ {"bearer": {} }},
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer",description="id of the post"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Action  Successfully",
     *          @OA\JsonContent()
     *       ),
     * )
     */


    // Fetch comment on a single post

    public function postComments(Post $post)
    {

        $data['postComments'] = Comment::with('user:id,first_name,last_name','user.profile:id,user_id,picture')->where('post_id', $post->id)->orderBy('created_at','desc')->get();


        $data['totalCommentCount'] = Comment::wherePost_id($post->id)->count();
        $data['totalViewCount'] = View::wherePost_id($post->id)->count();
        $data['totalImpressCount'] = Impression::wherePost_id($post->id)->count();
        if ($data){
            return $this->statusCode(200, 'Request  successful',['data'=>$data]);
        }else{
            return $this->statusCode(404, 'Request  Unsuccessful');
        }

    }





    /**
     * @OA\Delete(
     * path="/api/comments/{id}",
     * operationId="Unomment on  post by post id",
     * tags={"Unomment on post by post id"},
     * summary="Unomment on post by post id",
     * description="Unomment on post by post id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the post", type="number"),
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
    public function uncomment(Post $post)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $post_id = Comment::wherePost_id($post->id)->whereUser_id(auth()->id())->count();

                if ($post_id > 0) {
                    $comments = Comment::wherePost_id($post->id)->whereUser_id(auth()->id())->first();
                    $deleteComment = Comment::find($comments->id);
                    $deleteComment->delete();
                }

                return $this->statusCode(200, 'Request  successful');
            } else {
                return $this->statusCode(407, 'Please login');
            }

        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    // Reply comment on  post


    /**
     * @OA\POST(
     * path="/api/replies/{id}",
     * operationId="Reply on comments on a post by comment  id",
     * tags={"Reply on comments on a post by comment id"},
     * summary="Reply on comments on a post by comment id",
     * description="Reply on comments on a post by comment idhere",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the comment", type="number"),
     *               @OA\Property(property="reply", description="user reply on the comment", type="number"),
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
    public function reply(Request $request,Comment $comment)
    {

        try {
            if (request()->user('sanctum')) {


                $comment_id = Comment::whereId($comment->id)->count();

                if ($comment_id > 0) {

                    $data['comment'] = Reply::create([
                        "reply" => $request->reply,
                        "user_id" => auth()->id(),
                        "comment_id" => $comment->id,
                    ]);
                }

                return $this->statusCode(200, 'Request  successful',['data'=> $data]);
            } else {
                return $this->statusCode(407, 'Please login');
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    /**
     * @OA\POST(
     * path="/api/replies/comments{id}",
     * operationId="Get all  replies on a comments",
     * tags={"Get all  replies on a comments"},
     * summary="Get all  replies on a comments",
     * description="Get all  replies on a comments",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the comment", type="integer"),
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
    public function commentsReplies(Comment $comment)
    {

        $data['commentReply'] = Reply::with('user:id,first_name,last_name','user.profile:id,user_id,picture')->where('comment_id', $comment->id)->orderBy('created_at','desc')->get();

        if ($data){
            return $this->statusCode(200, 'Request  successful',['data'=>$data]);
        }else{
            return $this->statusCode(404, 'Request  Unsuccessful');
        }

    }


    //Delete reply on comment

    /**
     * @OA\DELETE(
     * path="/api/replies/{id}",
     * operationId="Delete reply on comments on a post by comment  id",
     * tags={"Delete reply on comments on a post by comment id"},
     * summary="Delete reply on comments on a post by comment id",
     * description="Delete reply on comment by comment id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the comment", type="number"),
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
    public function unreply(Comment $comment)
    {

        try {


            if (request()->user('sanctum')) {

                // check if current user following this business
                $reply_id = Reply::whereComment_id($comment->id)->whereUser_id(auth()->id())->count();

                if ($reply_id > 0) {
                    $reply = Reply::whereComment_id($comment->id)->whereUser_id(auth()->id())->first();
                    $deleteReply = Reply::find($reply->id);
                    $deleteReply->delete();
                }

                return $this->statusCode(200, 'Request  successful');
            } else {
                return $this->statusCode(407, 'Please login');
            }

        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }
}
