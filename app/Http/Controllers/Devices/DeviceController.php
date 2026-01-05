<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public create paringToken (Request $request)
    {
        // Validate the request data
        $user = $request->user();
        
    }
}
