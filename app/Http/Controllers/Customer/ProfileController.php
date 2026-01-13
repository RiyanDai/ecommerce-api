<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index');
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();
        $user->update($request->validated());

        return back()->with('success', 'Profile updated successfully!');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = auth()->user();
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password changed successfully!');
    }
}
