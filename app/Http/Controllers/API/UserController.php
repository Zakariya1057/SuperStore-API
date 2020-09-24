<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    public function register(Request $request)
    {

        $validated_data = $request->validate([
            'data.name' => ['required', 'string', 'max:255'],
            'data.email' => ['required', 'string', 'email', 'max:255'],
            'data.password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data = $validated_data['data'];

        if( User::where('name', $data['name'])->exists() ){
            return response()->json(['data' => ['error' => 'Name belongs to another user.']], 422);
        }

        if( User::where('email', $data['email'])->exists() ){
            return response()->json(['data' => ['error' => 'Email address belongs to another user.']], 422);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['data' => ['status' => 'sucess']]);
    }


}
