<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserAuth\AuthController;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\User;
// use Spatie\PdfToText\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser;

class BioController extends Controller
{
    /**
     * @OA\POST(
     * path="/api/uploadBio",
     * operationId="Upload CV in PDF Format here",
     * tags={"Upload CV"},
     * summary="User Upload CV in PDF Format here",
     * description="User Upload CV in PDF Format here.",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"bio_media"},
     *               @OA\Property(property="bio_media", type="file"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="File uploaded successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="File uploaded successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=403, description="Error in input fields"),
     *      @OA\Response(response=407, description="Please Login First"),
     *      @OA\Response(response=422, description="Unprocessed entity"),
     *      @OA\Response(response=500, description="Error occured while processing request"),
     * )
     */
    public function uploadPDF(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'bio_media' => ['required', 'file'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }

            // check if it's PDF or DOC/DOCX
            $acceptTypes = ['pdf', 'PDF'];
            $file = $request->file('bio_media');
            $file_type = $file->getClientOriginalExtension();
            $hasMatch = false;

            foreach ($acceptTypes as $types) {
                ($types == strtolower($file_type)) ? $hasMatch = true : '';
            }

            if (!$hasMatch) {
                return $this->statusCode(400, 'Invalid file type. Acceptable types are [' . implode(', ', $acceptTypes) . ']');
            }

            $pdfParser = new Parser();
            $pdf = $pdfParser->parseFile($file->path());
            $text = $pdf->getText();

            $profile = Profile::whereUser_id(auth()->id())->first();
            $profile->bio = $text;
            return $profile->save() ? $this->statusCode(200, "Bio Updated Successfully", ['text' => $text]) : $this->statusCode(422, 'Error occured. Try again later', ['bio' => '']);

            // $text = Pdf::getText($request->bio_media);
            // return $this->statusCode(200, 'File Uploaded successfully', ['text' => $text]);
        } catch (\Throwable $e) {
            // return $this->statusCode(500, $e->getMessage());
            return $this->statusCode(500, "Error occured while processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/bioPreview",
     * summary="Get user's bio information for edit",
     * description="Get user's bio information for edit",
     * tags={"Get user's bio information for edit"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Bio available",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     * )
     */
    public function bioPreview()
    {
        $chk = Profile::whereUser_id(auth()->id())->whereNotNull('bio')->count();
        if ($chk > 0) {
            $bio = Profile::whereUser_id(auth()->id())->first();
            return $this->statusCode(200, 'Bio Available', ['bio' => $bio->bio]);
        } else {
            return $this->statusCode(404, 'No data available', ['bio' => '']);
        }
    }

    /**
     * @OA\PATCH(
     * path="/api/saveUpdate",
     * operationId="Update user Biography",
     * tags={"Edit Biography"},
     * summary="User Update Biography",
     * description="User Update Biography",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"biography"},
     *               @OA\Property(property="biography", type="string"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Biography Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Biography Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=422, description="Unprocessed request"),
     *      @OA\Response(response=500, description="Server Error"),
     * )
     */
    public function saveUpdate(Request $request)
    {
        try {
            // VALIDATE INPUT FIELDS
            $validate = Validator::make(
                $request->all(),
                [
                    'bio_form' => ['required'],
                ]
            );

            if ($validate->fails()) {
                return $this->statusCode(403, "Error in input field(s)", ['error' => $validate->errors()]);
            }
            $profile = Profile::whereUser_id(auth()->id())->first();
            $profile->bio = $request->bio_form;
            return $profile->save() ? $this->statusCode(200, "Bio Updated Successfully", ['bio' => $request->bio_form]) : $this->statusCode(422, 'Error occured. Try again later', ['bio' => '']);
        } catch (\Throwable $e) {
            return $this->statusCode(500, "Error occured while processing your request");
        }
    }

    /**
     * @OA\Get(
     * path="/api/systemGenerateBio",
     * summary="System generate bio for User",
     * description="System generate bio for User",
     * tags={"System generate bio for User"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Bio Generated successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=401, description="Unauthenticated "),
     *      @OA\Response(response=422, description="Unprocessed request"),
     *      @OA\Response(response=500, description="Server error"),
     * )
     * )
     */
    public function generateBioFromProfileInformation()
    {
        // check if payment has been made and user has a pending bio-generation_status
        $chkTrans = Transaction::checkBioStatus()->count();
        if ($chkTrans == 0) {
            return $this->statusCode(422, 'No payment has been made. Make payment first');
        }

        $content = [];
        $user = User::find(auth()->id());
        $fullname = $user->last_name . ' ' . $user->first_name;
        try {
            $result = OpenAI::completions()->create([
                "model" => "text-davinci-003",
                "temperature" => 0.7,
                "top_p" => 1,
                "frequency_penalty" => 0,
                "presence_penalty" => 0,
                'max_tokens' => 800,
                'n' => 3,
                'prompt' => sprintf('Create a write up of 750 words on: %s into 10 paragraphs. The first paragraph will have a complete summary on: %s', $fullname, $fullname),
            ]);

            $content['sample1'] = trim($result['choices'][0]['text']);
            $content['sample2'] = trim($result['choices'][1]['text']);
            $content['sample3'] = trim($result['choices'][2]['text']);

            return $this->statusCode(200, "Bio Generated successfully", ['bio' => $content]);
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     * path="/api/bio/download/{user_id}",
     * summary="Download BIO as PDF",
     * description="Download BIO as PDF",
     * tags={"Download BIO as PDF"},
     * security={ {"bearer": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="SUCCESSFUL",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     * )
     */
    public function downloadBio($user_id)
    {
        try {
            $auth = new AuthController();
            $user = User::find($user_id);
            if ($user) {
                $bio = $user->profile()->select('bio')->first();
                $fn = ucwords(strtolower($bio->first_name)) . ' ' . ucwords(strtolower($bio->last_name)) . "'s Bio.pdf";
                return $auth->PDF_MAKER($bio->bio, $fn);
            }
            return $$this->statusCode(404, "No user found");
        } catch (\Throwable $e) {
            // return $this->statusCode(422, $e->getMessage());
            return $this->statusCode(422, "Error occured processing your request");
        }
    }
}
