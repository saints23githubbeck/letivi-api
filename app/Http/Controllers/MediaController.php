<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use FFMpeg;
use FFMpeg\Format\Video\WebM;

class MediaController extends Controller
{
    /**
     * Create a thumbnail of specified size
     *
     * @param Request $request file $request->file('photo)
     * @param string $path to save the thumbnail
     * @param string $inputName name of input field for file
     */
    public function createThumbnail(Request $request, ?string $path, ?string $inputName)
    {
        $thumbnail = [];
        // check image orientation and adjust values
        $imageFile = $request->file($inputName);
        $imagePath = $imageFile->getPathname();

        // Create GD image object
        $imageInfo = getimagesize($imagePath);
        $imageWidth = $imageInfo[0];
        $imageHeight = $imageInfo[1];

        // Check orientation
        $isPortrait = $imageHeight > $imageWidth;

        // Calculate the position of the rectangle
        switch ($isPortrait) {
            case true:
                $thumbnail['small'] = $this->convertToWebP($request, $path, 'media', 150, 300, 'small', true);
                $thumbnail['medium'] = $this->convertToWebP($request, $path, 'media', 300, 600, 'medium', true);
                $thumbnail['large'] = $this->convertToWebP($request, $path, 'media', 600, 1200, 'large', true);
                break;

            default:
                $thumbnail['small'] = $this->convertToWebP($request, $path, 'media', 800, 600, 'small', true);
                $thumbnail['medium'] = $this->convertToWebP($request, $path, 'media', 1366, 768, 'medium', true);
                $thumbnail['large'] = $this->convertToWebP($request, $path, 'media', 1920, 1080, 'large', true);
                break;
        }
        return $thumbnail;
    }

    /**
     * Create a small size thumbnail of image
     *
     * @param Request $request file $request->file('photo)
     * @param string $path to save the thumbnail
     * @param string $inputName name of input field for file
     */
    public function createSmallThumbnail(Request $request, ?string $path, ?string $inputName, ?bool $needWaterMark = false)
    {
        $thumbnail = [];
        $thumbnail['small'] = $this->convertToWebP($request, $path, 'media', 800, 600, 'small', $needWaterMark);
        return $thumbnail;
    }
    /**
     * Create a Medium size thumbnail of image
     *
     * @param Request $request file $request->file('photo)
     * @param string $path to save the thumbnail
     * @param string $inputName name of input field for file
     */
    public function createMediumThumbnail(Request $request, ?string $path, ?string $inputName, ?bool $needWaterMark = false)
    {
        $thumbnail = [];
        $thumbnail['medium'] = $this->convertToWebP($request, $path, 'media', 1366, 768, 'medium', $needWaterMark);
        return $thumbnail;
    }

    /**
     * Create a Large size thumbnail of image
     *
     * @param Request $request file $request->file('photo)
     * @param string $path to save the thumbnail
     * @param string $inputName name of input field for file
     */
    public function createLargeThumbnail(Request $request, ?string $path, ?string $inputName, ?bool $needWaterMark = false)
    {
        $thumbnail = [];
        $thumbnail['large'] = $this->convertToWebP($request, $path, 'media', 1920, 1080, 'large', $needWaterMark);
        return $thumbnail;
    }

    //
    public function save($path, $file)
    {
        $storage = Storage::disk('do')->putFile($path, $file, 'public');
        return $storage;
    }

    // this is only for test purpose
    public function retrieve($path)
    {
        $files = Storage::disk('do')->allFiles($path);
        return [$files, count($files)];
    }
    // end test

    public function destroyDirectory(?string $dir)
    {
        try {
            return Storage::disk('do')->deleteDirectory($dir);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function destroyFile($path)
    {
        try {
            return Storage::disk('do')->delete($path);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function destroyFileUsingApi(Request $request)
    {
        // return $request->path;
        try {
            Storage::disk('do')->delete($request->path);
            return $this->statusCode(200, 'File Deleted successfully');
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function download(?string $path, $mimeType)
    {
        try {
            if (!Storage::disk('do')->has($path)) {
                return $this->statusCode(404, 'Requested file does not exist');
            }

            $myArr = explode('/', $path);
            $filename = $myArr[count($myArr) - 1];

            $disk = Storage::disk('do');
            $stream = $disk->readStream($path);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $disk->mimeType($path),
                'Content-Length' => $disk->size($path),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    function convertToWebP(Request $request, ?string $path, ?string $inputName, ?int $width, ?int $height, ?string $type, ?bool $needWaterMark = false)
    {
        $image = $request->file($inputName);
        $filename = uniqid() . '.webp';

        // Load the image using Image Intervention
        $image = Image::make($image)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        if ($needWaterMark) {
            // Add watermark
            $businessName = 'Letivi'; // Replace with the domain name
            $workspace = "";
            // check if workspace is selected and get the name

            if ($request->event_id != null) {
                // check if id exists for the selected user
                $chk = Event::find($request->event_id);
                $name = "Owner: " . ucwords(strtolower($chk->name));
                $workspace = strlen($name) <= 30 ? $name : substr($name, 0, 30) . "...";
            }

            if ($request->project_id != null) {
                // check if id exists for the selected user
                $chk = Project::find($request->project_id);
                $name = "Owner: " . ucwords(strtolower($chk->name));
                $workspace = strlen($name) <= 30 ? $name : substr($name, 0, 30) . "...";
            }

            if ($request->business_id != null) {
                // check if id exists for the selected user
                $chk = Business::find($request->business_id);
                $name = "Owner: " . ucwords(strtolower($chk->name));
                $workspace = strlen($name) <= 30 ? $name : substr($name, 0, 30) . "...";
            }

            $ownerName = '';

            if ($workspace == "") {
                $fname = ucwords(strtolower(auth()->user()->first_name . " " . auth()->user()->last_name));
                $name = "Owner: " . substr($fname, 0, 30);
                $workspace = strlen($name) <= 30 ? $name : substr($name, 0, 30) . "...";
            }

            // Create a new canvas for the watermark
            $watermarkCanvas = Image::canvas($width, $height);

            // Set the watermark text styles
            $textStyles = [
                'color' => '#FFFFFF', // Font color (white in this example)
                'angle' => 0, // Rotation angle
            ];

            // Calculate the font size based on the image width
            $fontRatio = $width / 500; // Adjust the divisor as needed to control the font size

            $baseFontSize = 16; // Adjust the base font size as needed

            // Calculate the dynamic font sizes for business and owner names
            $businessFontSize = round($baseFontSize * $fontRatio);
            $ownerFontSize = round($baseFontSize * $fontRatio * 0.8); // Adjust the scaling factor as needed

            // Update the font sizes in the text styles array
            $textStyles['font'] = $businessFontSize;

            $imageFile = $request->file($inputName);
            $imagePath = $imageFile->getPathname();

            // Create GD image object
            $imageInfo = getimagesize($imagePath);
            $imageWidth = $imageInfo[0];
            $imageHeight = $imageInfo[1];

            // Check orientation
            $isPortrait = $imageHeight > $imageWidth;

            // Calculate the dimensions of the rectangle
            $rectanglePadding = 10; // Adjust the padding as needed
            $minimumRectangleHeight = $isPortrait ? $businessFontSize * 0.5 : $businessFontSize * 2 + $ownerFontSize + 80;
            $minimumOwnerTextHeight = $ownerFontSize + 10; // Adjust the padding as needed
            $rectangleHeight = max($minimumRectangleHeight, $businessFontSize * 2 + $ownerFontSize + 40, $minimumOwnerTextHeight * 2 + 20);

            $rectangleWidth = $width;

            // Calculate the position of the rectangle
            if ($isPortrait) {
                $rectangleX = $rectanglePadding;
                $rectangleY = ($height / 2) - ($rectangleHeight / 2);
            } else {
                $rectangleX = $width - $rectanglePadding - $rectangleWidth; // Adjust the calculation to subtract the rectangle width
                $rectangleY = ($height / 2) - ($rectangleHeight / 2);
            }

            // Create a semi-transparent background rectangle for the watermark
            $watermarkCanvas->rectangle($rectangleX, $rectangleY, $rectangleX + $rectangleWidth, $rectangleY + $rectangleHeight, function ($draw) use ($textStyles) {
                $draw->background('rgba(0, 0, 0, 0.3)');
            });

            // Merge the original image with the watermark background
            $image->insert($watermarkCanvas, 'top-left', 0, 0);

            // Calculate the vertical positions of the two words within the rectangle
            $businessNameY = $rectangleY + ($rectangleHeight / 2) - ($businessFontSize / 2);
            $workspaceY = $rectangleY + ($rectangleHeight / 2) + ($businessFontSize / 2);
            $ownerNameY = $rectangleY + ($rectangleHeight / 2) + ($businessFontSize / 2) + $ownerFontSize;


            // Add business name watermark
            $image->text($businessName, $rectangleX + ($rectangleWidth / 2), $businessNameY, function ($font) use ($textStyles, $businessFontSize) {
                $font->file(public_path('media/font/Gotham-Font/GothamBold.ttf')); // Replace with the actual path to the font file
                $font->size($businessFontSize);
                $font->color($textStyles['color']);
                $font->align('center'); // Change alignment to 'center'
                $font->valign('bottom');
                $font->angle($textStyles['angle']);
            });

            // Add workspace name watermark
            $image->text($workspace, $rectangleX + ($rectangleWidth / 2), $workspaceY, function ($font) use ($textStyles, $ownerFontSize) {
                $font->file(public_path('media/font/Gotham-Font/GothamLight.ttf')); // Replace with the actual path to the font file
                $font->size($ownerFontSize);
                $font->color($textStyles['color']);
                $font->align('center'); // Change alignment to 'center'
                $font->valign('top'); // Change valign to 'top'
                $font->angle($textStyles['angle']);
            });

            // Add owner name watermark
            $image->text($ownerName, $rectangleX + ($rectangleWidth / 2), $ownerNameY, function ($font) use ($textStyles, $ownerFontSize) {
                $font->file(public_path('media/font/Gotham-Font/GothamLight.ttf')); // Replace with the actual path to the font file
                $font->size($ownerFontSize);
                $font->color($textStyles['color']);
                $font->align('center'); // Change alignment to 'center'
                $font->valign('top'); // Change valign to 'top'
                $font->angle($textStyles['angle']);
            });
        }

        $maxSize = 50;
        switch ($type) {
            case 'small':
                $quality = 80;
                $maxSize = 10;
                break;
            case 'medium':
                $quality = 60;
                $maxSize = 30;
                break;
            case 'large':
                $quality = 30;
                $maxSize = 50;
                break;
        }

        // Encode the image with desired quality
        $image->encode('webp', $quality);

        // Compress and reduce file size to a maximum of 50kb

        $compressedImage = $this->compressImage($image, $maxSize);

        // Save the WebP image to DigitalOcean Spaces
        Storage::disk('do')->put($path . $type . '/' . $filename, $compressedImage, 'public');

        return $path . $type . '/' . $filename;
    }

    function compressImage($image, $maxFileSize)
    {
        $quality = 90;
        $originalSize = $image->filesize();

        // If the image size is already within the desired limit, return the original encoded data
        if ($originalSize <= $maxFileSize * 1024) {
            return $image->getEncoded();
        }

        // Iterate while reducing the quality to achieve the desired file size
        while ($originalSize > $maxFileSize * 1024 && $quality >= 10) {
            $image->encode('webp', $quality);
            $originalSize = strlen($image->getEncoded());
            $quality -= 10;
        }

        // Return the compressed image data
        return $image->getEncoded();
    }

    public function checkFileFormat(Request $request, ?string $inputName = null, $type = null): array
    {
        $response = [];
        // check if file is within acceptable formats
        $acceptTypes = ['mp4', 'mov', 'wmv', 'mkv', 'avi', 'mpeg4', 'jpg', 'svg', 'jpeg', 'heif', 'png', 'gif', 'heic'];
        $imageTypes = ['jpg', 'svg', 'jpeg', 'heif', 'png', 'gif', 'heic'];
        $videoTypes = ['mp4', 'mov', 'wmv', 'mkv', 'avi', 'mpeg4'];
        $otherImageTypes = ['pdf', 'psd'];

        $name = $inputName == null ? 'media' : $inputName;
        $file = $request->file($name);

        $file_type = strtolower($file->getClientOriginalExtension());

        $hasMatch = false;
        $response['hasMatch'] = $hasMatch;

        $mimeType = $file->getMimeType();
        $response['mimeType'] = $mimeType;

        $isTrueImage = false;
        $response['isTrueImage'] = $isTrueImage;

        switch ($type) {
            case 'image':
                // check if image type
                foreach ($imageTypes as $types) {
                    if ($types == $file_type) {
                        $hasMatch = true;
                        $isTrueImage = true;
                        $response['hasMatch'] = $hasMatch;
                        $response['isTrueImage'] = $isTrueImage;
                        $response['type'] = 'image';
                    }
                }
                !$hasMatch ? $acceptTypes = $imageTypes : '';
                break;
            case 'video':
                // check if video type
                foreach ($videoTypes as $types) {
                    if ($types == $file_type) {
                        $hasMatch = true;
                        $response['hasMatch'] = $hasMatch;
                        $response['type'] = 'video';
                    }
                }
                !$hasMatch ? $acceptTypes = $videoTypes : '';
                break;

            default:
                // check if image type
                foreach ($imageTypes as $types) {
                    if ($types == $file_type) {
                        $hasMatch = true;
                        $isTrueImage = true;
                        $response['hasMatch'] = $hasMatch;
                        $response['isTrueImage'] = $isTrueImage;
                        $response['type'] = 'image';
                    }
                }
                // check if video type
                foreach ($videoTypes as $types) {
                    if ($types == $file_type) {
                        $hasMatch = true;
                        $response['hasMatch'] = $hasMatch;
                        $response['type'] = 'video';
                    }
                }

                // check if other image type
                foreach ($otherImageTypes as $types) {
                    if ($types == $file_type) {
                        $hasMatch = true;
                        $response['hasMatch'] = $hasMatch;
                        $response['type'] = 'image';
                    }
                }
                break;
        }

        if (!$hasMatch) {
            $response['msg'] = 'Invalid file type. Acceptable types are [' . implode(', ', $acceptTypes) . ']';
        }

        return $response;
    }

    // video manipulation
    public function createVideoThumbnail(Request $request, ?string $path, ?string $inputName)
    {
        $name = $inputName == null ? 'media' : $inputName;
        $video = $request->file($name);
        $convertedName = uniqid() . '.webm';

        $videoName = $video->getClientOriginalName();
        $path = $video->storeAs('videos', $videoName, 'ffmpeg');

        $convertedPath = 'videos/' . $convertedName;

        FFMpeg::fromDisk('public')
        ->open($path)
        ->export()
        ->toDisk('public')
        ->inFormat(new WebM())
        ->save(public_path($convertedPath));
    }
}
