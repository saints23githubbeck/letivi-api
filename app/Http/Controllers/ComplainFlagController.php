<?php

namespace App\Http\Controllers;

use App\Models\ComplainFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComplainFlagController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/flag/save",
     * operationId="Save Flag For complain",
     * tags={"Save Flag For complain"},
     * summary="Save Flag For complain",
     * description="Save Flag For complain here",
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
     *          response=200,
     *          description="Flag recorded   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=201,
     *          description="Flag recorded   Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=409, description="Duplicate records"),
     *      @OA\Response(response=500, description="Server Error"),
     * )
     */
    public function newFlag(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'name' => ['required', 'max:50'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if name exists
            $chk = ComplainFlag::whereName(trim(strtolower($request->name)))->count();
            if ($chk > 0) {
                return $this->statusCode(409, 'Flag name already exists');
            }

            return DB::transaction(function () use ($request) {
                $flag = new ComplainFlag();
                $flag->name = trim(strtolower($request->name));
                if ($flag->save()) {
                    return $this->statusCode(200, "Flag recorded successfully", ['flag' => $flag]);
                } else {
                    return $this->statusCode(500, "Error occured whiles processing your request");
                }
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Patch(
     * path="/api/flag/{flag_id}",
     * operationId="Update Complain flag",
     * tags={"Update Complain flag "},
     * summary="Update albulm",
     * description="Update Complain flag here",
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
     *          description="Flag Updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Flag Updated  Successfully",
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
     *      @OA\Response(response=409, description="Duplicat entry"),
     * )
     */
    public function editFlag($flag_id, Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'name' => ['required', 'max:50'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if name exists
            $chk = ComplainFlag::whereName(trim(strtolower($request->name)))->count();
            if ($chk > 0) {
                return $this->statusCode(409, 'Flag name already exist');
            }
            $chk = ComplainFlag::whereId($flag_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No flag match your request');
            }

            $flag = ComplainFlag::find($flag_id);
            return DB::transaction(function () use ($request, $flag) {
                $flag->name = trim(strtolower($request->name));
                if ($flag->save()) {
                    return $this->statusCode(200, "Flag updated successfully", ['flag' => $flag]);
                } else {
                    return $this->statusCode(500, "Error occured whiles processing your request");
                }
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Delete(
     * path="/api/flag/{flag_id}",
     * operationId="Delete Flag",
     * tags={"Delete Flag "},
     * summary="Delete Flag",
     * description="Delete Flag here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(

     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Flag Deleted  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Flag Deleted  Successfully",
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
     *      @OA\Response(response=500, description="server error"),
     * )
     */
    public function deleteFlag($flag_id)
    {
        try {

            $chk = ComplainFlag::whereId($flag_id)->count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No flag match your request');
            }

            $flag = ComplainFlag::find($flag_id);
            return DB::transaction(function () use ($flag) {
                if ($flag->delete()) {
                    return $this->statusCode(200, "Flag deleted successfully");
                } else {
                    return $this->statusCode(500, "Error occured whiles processing your request");
                }
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/flag/all",
     * operationId="Get all complain flags",
     * tags={"Get all complain flags"},
     * summary="Get all complain flags",
     * description="Get all complain flags",
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
    public function allFlags()
    {
        try {

            $chk = ComplainFlag::count();
            if ($chk == 0) {
                return $this->statusCode(404, 'No record available');
            }

            $flag = ComplainFlag::all();
            return $this->statusCode(200, "Flags available", ['flag' => $flag]);
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
            //return $this->statusCode(422, "Error occured whiles processing your request");
        }
    }
}
