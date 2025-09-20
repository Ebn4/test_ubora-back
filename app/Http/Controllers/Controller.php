<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;


/**
     * @OA\Info(
     *      version="1.0.0",
     *      title="Documentation Ubora Assessments",
     *      description="L5 Swagger OpenApi description",
     *
     * )
     *
     *

     *
     * @OA\Tag(
     *     name="Ubora Assessments backend",
     *     description="API Endpoints of Ubora Assessments backend"
     * )
     */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
