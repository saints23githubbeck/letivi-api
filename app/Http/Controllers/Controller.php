<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Lativi  API Documentaion ",
 *      description=" Documented by Arthur Shadrack, Contact: +233 243 33 44 84 ",
 *      @OA\Contact(
 *          email="arthurshadrack45@gmail.com "
 *      ),
 * )
 */

class Controller extends StatusCodeController
{

    use AuthorizesRequests, ValidatesRequests, DispatchesJobs;

}
