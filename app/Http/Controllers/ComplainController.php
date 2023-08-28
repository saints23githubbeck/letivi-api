<?php

namespace App\Http\Controllers;

use App\Models\Complain;
use App\Models\ComplainFlag;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComplainController extends Controller
{

    /**
     * @OA\POST(
     * path="/api/complain/post",
     * operationId="Report post",
     * tags={"Report posts"},
     * summary="Report posts",
     * description="User Report posts",
     *      @OA\Response(
     *          response=201,
     *          description="Complain recorded Successfully",
     *          @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"flag_id", "post_id"},
     *               @OA\Property(property="flag_id", type="integer"),
     *               @OA\Property(property="post_id", type="integer"),
     *               @OA\Property(property="message", type="string"),
     *            ),
     *        ),
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Complain recorded Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     *      @OA\Response(response=422, description="Unprocessed entity"),
     * )
     */

    public function reportPost(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'flag_id' => ['required', 'integer'],
                    'message' => ['nullable', 'max:100'],
                    'post_id' => ['required', 'integer']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if post id exists
            $chk = Post::whereId($request->post_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No post match your request');
            }

            // check if flag id exist
            $chk = ComplainFlag::whereId($request->flag_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No flag match your request');
            }

            return DB::transaction(function () use ($request) {
                $complain = new Complain();
                $complain->flag_id = $request->flag_id;
                $complain->description = $request->message;
                $complain->user_id = auth()->id();
                $complain->post_id = $request->post_id;
                if ($complain->save()) {
                    return $this->statusCode(200, "Complain recorded successfully", ['complain' => $complain]);
                } else {
                    return $this->statusCode(422, "Error occured whiles processing your request");
                }
            });
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Delete(
     * path="/api/complain/undo/{complain_id}",
     * operationId="Delete/Undo Reported Post",
     * tags={"Delete/Undo Reported Post"},
     * summary="Delete/Undo Reported Post",
     * description="Delete Reported Post here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Reported Post deleted successfully.",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function undoReport($complain_id)
    {
        try {
            $chk = Complain::whereId($complain_id)->whereUser_id(auth()->id())->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No complain found');
            }

            return DB::transaction(function () use ($complain_id) {
                $complain = Complain::find($complain_id);
                return $complain->delete() ? $this->statusCode(200, "Report removed successfully") : $this->statusCode(422, 'Error occured processing your request');
            });
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/complain",
     * operationId="Get all reported posts by current login user",
     * tags={"Get all reported posts by current login user"},
     * summary="Get all reported posts by current login user",
     * description="Get all reported posts by current login user",
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
    public function myComplains()
    {
        try {
            // check if user has complains
            $chk = Complain::whereUser_id(auth()->id())->count();
            if ($chk > 0) {
                $complain = Complain::whereUser_id(auth()->id())->paginate(30);
                return $this->statusCode(200, 'Records available', ['complain' => $complain]);
            } else {
                return $this->statusCode(404, 'No record available');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }
}
