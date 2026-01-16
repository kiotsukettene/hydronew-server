<?php

namespace App\Http\Controllers\AccountSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountSettings\UpdateAccountRequest;
use App\Http\Requests\AccountSettings\UpdatePasswordRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $ownedDevicesCount = $user->devices()->count();
        $ownedDevices = $user->devices()->get(); // optional if you want device info

        return response()->json([
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'address' => $user->address,
                'owned_devices_count' => $ownedDevicesCount,
                'devices' => $ownedDevices, // optional, include device details
                'profile_picture' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]
        ]);
    }


    public function update(UpdateAccountRequest $request, User $user)
    {
        $user = $request->user();

        $validated = $request->validated();


        $user->update($validated);

        return response()->json([
            'message' => 'Account updated successfully.',
            'data' => $user,
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        $user = $request->user();

        if (!$request->hasFile('profile_picture')) {
            return response()->json([
                'message' => 'No file uploaded.',
                'debug' => $request->all(),
            ], 400);
        }

        $validated = $request->validate([
            'profile_picture' => 'required|image|max:2048',
        ]);

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->profile_picture = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'data' => [
                'id' => $user->id,
                'profile_picture' => asset('storage/' . $path),
            ],
        ]);
    }


    public function updatePassword(UpdatePasswordRequest $request, User $user)
    {
        $user = $request->user();

        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function loginHistory(Request $request)
    {
        $history = $request->user()->loginHistories()->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'data' => $history,
        ]);
    }
}


// Account Functions

// index -> view of account
// -> First Name + Last Name
// -> Email
// -> Owned Devices
// -> Electric Volts (for MFC)
// -> Predicted Yield (ig)

// Manage Account

// update -> update null fields

// -> First Name
// -> Last Name
// -> Address
// -> Profile Picture

// Change password

// updatePassword -> update password


// Login History

// loginHistory -> check login history of the user

// Permissions


// appPermission -> updates notification, file storage, restore settings
