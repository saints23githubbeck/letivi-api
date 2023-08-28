<?php

namespace App\Http\Controllers;

use App\Models\Carer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarerController extends Controller
{
    private $path;
    private $media;

    public function __construct()
    {
        $this->path = 'cv/';
        $this->media = new MediaController();
    }

    /**
     * @OA\Post(
     * path="/api/carers",
     * operationId="Create carer",
     * tags={"Create carer "},
     * summary="Create carer",
     * description="create carer here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"full_name","email","description","cv"},
     *               @OA\Property(property="full_name", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="cv", type="file"),
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
        $path = '';
        try {
            $request->validate([
                'full_name' => 'required|string',
                'description' => 'required|string',
                'cv' => 'required|file|max:20480', //not beyond 20mb
                'email' => 'required|email',
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(407, $e->getMessage());
        }

        try {
           return DB::transaction(function () use ($request) {
                $acceptTypes = ['docx', 'DOCX', 'pdf', 'PDF'];
                $file = $request->file('cv');
                $file_type = $file->getClientOriginalExtension();
                $hasMatch = false;

                foreach ($acceptTypes as $types) {
                    ($types == $file_type) ? $hasMatch = true : '';
                }

                if (!$hasMatch) {
                    return $this->statusCode(400, "Invalid file type. Acceptable types are 'docx','DOCX','pdf','PDF'");
                }

                $this->path .= auth()->id();
                $path = $this->media->save($this->path, $request->file('cv'));
                $data['carer'] = Carer::create([
                    'full_name' => $request->full_name,
                    'description' => $request->description,
                    'cv' => $path ?? null,
                    'email' => $request->email,
                ]);

                if ($data) {
                    return $this->statusCode(200, 'Request successful', ['data' => $data]);
                } else {
                    $this->media->destroyFile($path);
                    return $this->statusCode(400, 'Request unsuccessful');
                }
            });
        } catch (\Throwable $th) {
            $this->media->destroyFile($path);
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }


    // Delete carer page

    /**
     * @OA\DELETE(
     * path="/api/careres/{id}",
     * operationId="Delete carer by id",
     * tags={"Delete carer by id"},
     * summary="Delete carer by id",
     * description="Delete carer by id here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", description="id of the carer", type="number"),
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

    public function destroy(Carer $carer)
    {
        try {
            $carer_id = Carer::whereId($carer->id)->count();
            if ($carer_id > 0) {
              return  DB::transaction(function () use ($carer) {
                    $cv = $carer->cv;
                    $carer->delete();
                    $data['carer'] = $carer;
                    if ($data) {
                        $this->media->destroyFile($cv);
                        return $this->statusCode(200, 'Request successful', ['data' => $data]);
                    } else {
                        return $this->statusCode(400, 'Request unsuccessful');
                    }
                });
            }
        } catch (\Throwable $th) {
            return $this->statusCode(500, "We couldn't process your request, please try again.");
        }
    }
}
