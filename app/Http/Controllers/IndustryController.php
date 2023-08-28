<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class IndustryController extends Controller
{

    // create industries
    /**
     * @OA\Post(
     * path="/api/industries",
     * operationId="Create industry",
     * tags={"Create industry "},
     * summary="Create industry",
     * description="create industry here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name"},
     *               @OA\Property(property="name", type="string"),
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

    public function create(Request $request)

    {
        $request->validate([
            'name' => 'required|string',
        ]);


        try {
            $trans = DB::transaction(function () use ($request) {

                $data['industries'] = Industry::create([
                    'name' => $request->name,
                ]);


                if ($data) {
                    return $this->statusCode(200, 'Request successful',['data'=>$data]);
                } else {

                    return $this->statusCode(400, 'Request unsuccessful');
                }
            });
            return $trans;
        } catch (\Throwable $th) {
            // return $th->getMessage();
            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    /**
     * @OA\Get(
     * path="/api/industries",
     * operationId="Auto complete industries name",
     * tags={"Auto complete industries name "},
     * summary="Auto complete industries name",
     * description="Auto complete industries name here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name"},
     *               @OA\Property(property="name", type="string"),
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

    public function getIndustries(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        try {

                $data['industries'] = Industry::orderBy('name','asc')->get();

                //checking if request exit
                if ($data) {

                    return $this->statusCode(200, 'Request successful',['data'=>$data]);
                } else {

                    return $this->statusCode(404, 'Request successful');
                }

        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }

    /**
     * @OA\Get(
     * path="/api/industries/all",
     * summary="Get all industries name",
     * description="Get all industries name",
     * tags={"Get all industries name"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Industries available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     * )
     */
    public function allIndustries()
    {
        try {

                $data['industries'] = Industry::orderBy('created_at','asc')->get();

                //checking if request exit
                if ($data) {

                    return $this->statusCode(200, 'Request successful',['data'=>$data]);
                } else {

                    return $this->statusCode(404, 'Request successful');
                }

        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }
}
