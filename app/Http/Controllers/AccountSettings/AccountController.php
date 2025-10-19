<?php

namespace App\Http\Controllers\AccountSettings;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        $user->update($validated);

        return response()->json(['message' => 'Account updated successfully.']);
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
