<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalInfo;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfessionalInfoController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/professionalInfo",
     * summary="Get single user's professional Info",
     * description="Get single user's professional Info",
     * tags={"Get single user's professional Info"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Data available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     * )
     */
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chk = ProfessionalInfo::whereUser_id(auth()->id())->count();
        if ($chk > 0) {
            $professionalInfo = $this->infoReturned();
            return $this->statusCode(200, 'Resource found', $professionalInfo);
        }
        return $this->statusCode(404, 'No resource found');
    }

    /**
     * @OA\POST(
     * path="/api/professionalInfo",
     * operationId="Create New Professional Info",
     * tags={"Professional Info"},
     * summary="User Create New Professional Info",
     * description="User Create New Professional Info here.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={},
     *               @OA\Property(property="organization[]", type="string"),
     *               @OA\Property(property="awards[]", type="string"),
     *               @OA\Property(property="nomination[]", type="string"),
     *               @OA\Property(property="qualification[]", type="string"),
     *               @OA\Property(property="work_experience", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Professional Info Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Professional Info Created  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */
    public function create(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'organization' => ['sometimes', 'array'],
                    'awards' => ['sometimes', 'array'],
                    'nomination' => ['sometimes', 'array'],
                    'qualification' => ['sometimes', 'array'],
                    'work_experience' => ['nullable', 'integer']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $hasOneNotEmpty = ($request->has('organization') || $request->has('awards') || $request->has('nomination') || $request->has('qualification') || $request->has('work_experience')) ? true : false;

            if (!$hasOneNotEmpty) {
                return $this->statusCode(403, "All fields are empty. At least one field must have a value");
            }

            return DB::transaction(function () use ($request) {
                $myArr = [];
                if ($request->has('organization')) {
                    // convert to array
                    $organization = \json_decode(json_encode($request->organization, 1), true);
                    // count array before u begin operation
                    if (count($organization) > 0) {

                        $hasNulledValue = false;
                        for ($i = 0; $i < count($organization); $i++) {
                            $myArr[] = [
                                'organization' => $organization[$i]['organization'],
                                'role' => $organization[$i]['role'],
                                'country' => $organization[$i]['country'],
                                'user_id' => auth()->id(),
                                'awards' => null,
                                'nomination' => null,
                                'qualification' => null,
                                'education' => null,
                                'work_experience' => null,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($organization[$i]['organization'] == null || $organization[$i]['role'] == null || $organization[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if ($hasNulledValue) {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }


                if ($request->has('awards')) {
                    // convert to array
                    $awards = \json_decode(json_encode($request->awards, 1), true);

                    // count array before u begin operation
                    if (count($awards) > 0) {
                        // loop array
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($awards); $i++) {
                            $myArr[] = [
                                'organization' => null,
                                'role' => null,
                                'country' => $organization[$i]['country'],
                                'user_id' => auth()->id(),
                                'awards' => $awards[$i]['awards'],
                                'nomination' => null,
                                'qualification' => null,
                                'education' => null,
                                'work_experience' => null,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($awards[$i]['awards'] == null || $awards[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if ($hasNulledValue) {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('nomination')) {
                    // convert to array
                    $nomination = \json_decode(json_encode($request->nomination, 1), true);
                    // count array before u begin operation
                    if (count($nomination) > 0) {
                        // loop array

                        $hasNulledValue = false;
                        for ($i = 0; $i < count($nomination); $i++) {
                            $myArr[] = [
                                'organization' => null,
                                'role' => null,
                                'country' => $organization[$i]['country'],
                                'user_id' => auth()->id(),
                                'awards' => null,
                                'nomination' => $nomination[$i]['nomination'],
                                'qualification' => null,
                                'education' => null,
                                'work_experience' => null,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($nomination[$i]['nomination'] == null || $nomination[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if ($hasNulledValue) {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('qualification')) {
                    // convert to array
                    $qualification = \json_decode(json_encode($request->qualification, 1), true);
                    // count array before u begin operation
                    if (count($qualification) > 0) {
                        // loop array
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($qualification); $i++) {
                            $myArr[] = [
                                'organization' => null,
                                'role' => null,
                                'country' => null,
                                'user_id' => auth()->id(),
                                'awards' => null,
                                'nomination' => null,
                                'qualification' => $qualification[$i]['qualification'],
                                'education' => $qualification[$i]['education'],
                                'work_experience' => null,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($qualification[$i]['qualification'] == null || $qualification[$i]['education'] == null) ? $hasNulledValue = true : '';
                        }

                        if ($hasNulledValue) {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('work_experience')) {
                    $myArr[] = [
                        'organization' => null,
                        'role' => null,
                        'country' => null,
                        'user_id' => auth()->id(),
                        'awards' => null,
                        'nomination' => null,
                        'qualification' => null,
                        'education' => null,
                        'work_experience' => $request->work_experience,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];
                }
                // return $myArr;
                $save = ProfessionalInfo::insert($myArr);

                $professionalInfo = $this->infoReturned();

                return ($save) ? $this->statusCode(200, 'Record saved successfully', $professionalInfo) : $this->statusCode(500, "Error occured whiles processing your request. Try again later");
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     * path="/api/professionalInfo/{user_id}",
     * summary="Get single user's professional Info",
     * description="Get single user's professional Info",
     * tags={"Get single user's professional Info"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Data available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessed entity"),
     * )
     * )
     */

    public function show($user_id)
    {
        try {
            $chk = ProfessionalInfo::whereUser_id($user_id)->count();
            if ($chk > 0) {
                $professionalInfo = $this->infoReturned();
                return $this->statusCode(200, 'Resource found', $professionalInfo);
            }
            return $this->statusCode(404, 'Resource not found');
        } catch (\Throwable $e) {
            return $this->statusCode(422, 'Error occured while processing your request');
        }
    }

    /**
     * @OA\PATCH(
     * path="/api/professionalInfo",
     * operationId="Update Professional Info",
     * tags={"Edit Professional Info"},
     * summary="User Update Professional Info",
     * description="User Update Professional Info here.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={},
     *               @OA\Property(property="organization[]", type="string"),
     *               @OA\Property(property="awards[]", type="string"),
     *               @OA\Property(property="nomination[]", type="string"),
     *               @OA\Property(property="qualification[]", type="string"),
     *               @OA\Property(property="work_experience", type="integer"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Professional Info updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Professional Info updated  Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'organization' => ['sometimes', 'array'],
                    'awards' => ['sometimes', 'array'],
                    'nomination' => ['sometimes', 'array'],
                    'qualification' => ['sometimes', 'array'],
                    'work_experience' => ['nullable', 'integer']
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            $hasOneNotEmpty = ($request->has('organization') || $request->has('awards') || $request->has('nomination') || $request->has('qualification') || $request->has('work_experience')) ? true : false;

            if (!$hasOneNotEmpty) {
                return $this->statusCode(403, "All fields are empty. At least one field must have a value");
            }

            return DB::transaction(function () use ($request) {
                if ($request->has('organization')) {
                    // convert to array
                    $organization = \json_decode(json_encode($request->organization, 1), true);
                    // count array before u begin operation
                    if (count($organization) > 0) {
                        // loop array
                        $myArr = [];
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($organization); $i++) {
                            $myArr[] = [
                                'organization' => $organization[$i]['organization'],
                                'role' => $organization[$i]['role'],
                                'country' => $organization[$i]['country'],
                                'user_id' => auth()->id(),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($organization[$i]['organization'] == null || $organization[$i]['role'] == null || $organization[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            // delete the old records and insert a new one
                            $chk = ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('organization')->whereNotNull('role')->whereNotNull('country')->count();
                            if ($chk > 0) {
                                ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('organization')->whereNotNull('role')->whereNotNull('country')->delete();
                            }
                            ProfessionalInfo::insert($myArr);
                        } else {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }


                if ($request->has('awards')) {
                    // convert to array
                    $awards = \json_decode(json_encode($request->awards, 1), true);

                    // count array before u begin operation
                    if (count($awards) > 0) {
                        // loop array
                        $myArr = [];
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($awards); $i++) {
                            $myArr[] = [
                                'awards' => $awards[$i]['awards'],
                                'country' => $awards[$i]['country'],
                                'user_id' => auth()->id(),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($awards[$i]['awards'] == null || $awards[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            // delete the old records and insert a new one
                            $chk = ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('awards')->whereNotNull('country')->count();
                            if ($chk > 0) {
                                ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('awards')->whereNotNull('country')->delete();
                            }
                            ProfessionalInfo::insert($myArr);
                        } else {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('nomination')) {
                    // convert to array
                    $nomination = \json_decode(json_encode($request->nomination, 1), true);
                    // count array before u begin operation
                    if (count($nomination) > 0) {
                        // loop array
                        $myArr = [];
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($nomination); $i++) {
                            $myArr[] = [
                                'nomination' => $nomination[$i]['nomination'],
                                'country' => $nomination[$i]['country'],
                                'user_id' => auth()->id(),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];
                            ($nomination[$i]['nomination'] == null || $nomination[$i]['country'] == null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            // delete the old records and insert a new one
                            $chk = ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('nomination')->whereNotNull('country')->count();
                            if ($chk > 0) {
                                ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('nomination')->whereNotNull('country')->delete();
                            }
                            ProfessionalInfo::insert($myArr);
                        } else {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('qualification')) {
                    // convert to array
                    $qualification = \json_decode(json_encode($request->qualification, 1), true);
                    // count array before u begin operation
                    if (count($qualification) > 0) {
                        // loop array
                        $myArr = [];
                        $hasNulledValue = false;
                        for ($i = 0; $i < count($qualification); $i++) {
                            $myArr[] = [
                                'qualification' => $qualification[$i]['qualification'],
                                'education' => $qualification[$i]['education'],
                                'user_id' => auth()->id(),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            ($qualification[$i]['qualification'] == null || $qualification[$i]['education'] == null) ? $hasNulledValue = true : '';
                        }

                        if (!$hasNulledValue) {
                            // delete the old records and insert a new one
                            $chk = ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('qualification')->whereNotNull('education')->count();
                            if ($chk > 0) {
                                ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('qualification')->whereNotNull('education')->delete();
                            }
                            ProfessionalInfo::insert($myArr);
                        } else {
                            throw new Exception("Cannot process array value of null", 1);
                        }
                    }
                }

                if ($request->has('work_experience')) {
                    // delete the old records and insert a new one
                    $chk = ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('work_experience')->count();
                    if ($chk > 0) {
                        ProfessionalInfo::whereUser_id(auth()->id())->whereNotNull('work_experience')->delete();
                    }

                    $info = new ProfessionalInfo();
                    $info->work_experience = $request->work_experience;
                    $info->user_id = auth()->id();
                    $info->save();
                }

                $professionalInfo = $this->infoReturned();

                return $this->statusCode(200, 'Record updated successfully', $professionalInfo);
            });
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     * path="/api/professionalInfo/{professionalInfo}",
     * summary="Delete user's professional info",
     * description="Delete user's professional info",
     * tags={"Delete user's professional info"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Professional Info deleted successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=401, description="Error occured while processing request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=407, description="Please Login First"),
     * )
     * )
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProfessionalInfo $professionalInfo)
    {
        try {
            if ($professionalInfo) {
                return ($professionalInfo->delete()) ? $this->statusCode(200, 'Resurce deleted successfully') : $this->statusCode(500, "Error occured whiles processing your request. Try again later");
            } else {
                return $this->statusCode(404, 'Resource not found');
            }
        } catch (\Throwable $e) {
            return $this->statusCode(500, 'Error occured while processing your request');
        }
    }

    private function infoReturned()
    {
        $data = [];
        $data['professionalInfo'] = [
            'organization' => ProfessionalInfo::select('id', 'organization', 'role', 'country')->whereNotNull('organization')->whereNotNull('role')->whereNotNull('country')->whereUser_id(auth()->id())->get(),
            'awards' => ProfessionalInfo::select('id', 'awards', 'country')->whereNotNull('awards')->whereNotNull('country')->whereUser_id(auth()->id())->get(),
            'nomination' => ProfessionalInfo::select('id', 'nomination', 'country')->whereNotNull('nomination')->whereNotNull('country')->whereUser_id(auth()->id())->get(),
            'qualification' => ProfessionalInfo::select('id', 'qualification', 'education')->whereNotNull('qualification')->whereNotNull('education')->whereUser_id(auth()->id())->get(),
            'work_experience' => ProfessionalInfo::select('id', 'work_experience')->whereNotNull('work_experience')->whereUser_id(auth()->id())->first()
        ];
        return $data;
    }
}
