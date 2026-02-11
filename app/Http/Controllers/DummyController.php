<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

final class DummyController extends Controller
{
    public function ok()
    {
        return ApiResponse::success(['pong' => true], 'Pong');
    }
}
