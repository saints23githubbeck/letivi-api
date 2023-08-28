<?php

namespace App\Http\Controllers;

use App\Models\NewsLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsLetterController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/newsletters",
     * operationId="Create news letter",
     * tags={"Create news letter "},
     * summary="Create news letter",
     * description="create news letter here",
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
     *          description="Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Created  Successfully",
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
    //create carer
    public function create(Request $request)
    {
     $data = '';
        $request->validate([
            'email' => 'required|string',
        ]);
//        return $request;
        try {
            if (NewsLetter::whereEmail($request->email)->count() > 0){

                return $this->statusCode(400, 'You have already subscribed');
            }


            $trans = DB::transaction(function () use ($request) {
                $data['newsletter'] = NewsLetter::create([
                    'email' => $request->email,
                ]);
            if ($data)
                    return $this->statusCode(200, "Post saved successfully", ['post' => $data]);
                 else {

                    return $this->statusCode(500, "Error occured whiles processing your request");
                }
            });
            return $trans;


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }


}
