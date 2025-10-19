<?php

namespace App\Http\Controllers\AccountSettings;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $ownedDevices = Device::where('user_id', $user->id)->count();

        $fullName = $user->first_name . ' ' . $user->last_name;
        $email = $user->email;


        return response()->json([
            'full_name' => $fullName,
            'email' => $email,
            'owned_devices_count' => $ownedDevices,
        ]);
    }
}
