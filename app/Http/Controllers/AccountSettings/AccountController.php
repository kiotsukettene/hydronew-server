<?php

namespace App\Http\Controllers\AccountSettings;

use App\Http\Controllers\Controller;
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

        $ownedDevices = Device::where('user_id', $user->id)->count();

        $fullName = $user->first_name . ' ' . $user->last_name;
        $email = $user->email;


        return response()->json([
            'data' => [
                'full_name' => $fullName,
                'email' => $email,
                'owned_devices_count' => $ownedDevices,
            ]
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Account updated successfully.',
            'data' => $user,
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8) // require at least 8 characters
                    ->letters()   // must contain letters
                    ->numbers()   // must contain numbers
                    ->mixedCase() // must contain uppercase + lowercase
                    ->symbols(),  // must contain symbols
            ],
        ]);

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
