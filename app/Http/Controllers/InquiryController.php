<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InquiryController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/enquiries",
     * operationId="Create enquiry",
     * tags={"Create enquiry "},
     * summary="Create enquiry",
     * description="create enquiry here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"full_name","message","organization","subject","email","phone_number"},
     *               @OA\Property(property="full_name", type="string"),
     *               @OA\Property(property="message", type="string"),
     *               @OA\Property(property="organization", type="string"),
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="phone_namber", type="number"),
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
            'full_name' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string',
            'organisation' => 'required',
            'email' => 'required|string',
            'phone_number' => 'required|string',
        ]);



        try {

            DB::transaction(function () use ($request) {



                $data['enquiries'] = Inquiry::create([
                    'full_name' => $request->full_name,
                    'subject' => $request->subjject,
                    'message' => $request->message,
                    'organisation' => $request->organisation,
                    'phone_number' => $request->phone_number,
                    'email' => $request->email,
                ]);


                if ($data) {

                    return $this->statusCode(200, 'Request successful',['data'=>$data]);
                } else {

                    return $this->statusCode(400, 'Request unsuccessful');
                }
            });

        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }


    // Delete business page

    /**
     * @OA\DELETE(
     * path="/api/enquiries/{id}",
     * operationId="Delete enquiry bi id",
     * tags={"Delete enquiry by id"},
     * summary="Delete enquiry by id",
     * description="Delete enquiry by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the enquiry", type="number"),
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
    public function destroy( Inquiry $inquiry)
    {


        try {
            $inquiry_id = Inquiry::whereId($inquiry->id)->count();
            if ($inquiry_id >0 ){
                DB::transaction(function () use ($inquiry) {

                    $inquiry->delete();

                    $data['inquiry'] = $inquiry;

                    if ($data) {

                        return $this->statusCode(200, 'Request successful',['data'=>$data]);
                    } else {

                        return $this->statusCode(400, 'Request unsuccessful');
                    }
                });
            }


        } catch (\Throwable $th) {

            return response()->json([
                'message' => "We couldn't process your request, please try again."
            ]);

        }

    }
}
