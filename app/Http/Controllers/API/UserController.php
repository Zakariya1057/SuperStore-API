<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{

    use UserTrait;

    public function register(Request $request){

        $validated_data = $request->validate([
            'data.name' => [],
            'data.email' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
        ]);

        $data = $validated_data['data'];

        $nameError = $this->validate_field($data,'name');
        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'new_password');

        if($nameError || $passwordError || $emailError){
            return $nameError ?? $passwordError ?? $emailError;
        }

        if( User::where('email', $data['email'])->exists() ){
            return response()->json(['data' => ['error' => 'Email address belongs to another user.']], 422);
        }
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken($user->id)->plainTextToken;

        User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);

        return response()->json(['data' => ['token' => $token, 'name' => $user->name, 'email' => $user->email]]);
    }

    public function login(Request $request){

        $validated_data = $request->validate([
            'data.email' => [],
            'data.password' => []
        ]);
        
        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'password');

        if($emailError || $passwordError){
            return $emailError ?? $passwordError;
        }

        $user = User::where('email', $data['email'])->get()->first();

        if(!$user){
            return response()->json(['data' => ['error' => 'Email address doesn\'t belongs to any user.']], 404);
        }

        if (Hash::check($data['password'], $user->password)) {
            $user->tokens()->delete();
            $token = $user->createToken($user->id)->plainTextToken;
            User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);
            return response()->json(['data' => ['token' => $token, 'name' => $user->name, 'email' => $user->email]]);
        } else {
            return response()->json(['data' => ['error' => 'Wrong password.']], 404);
        }

    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        User::where('id', $request->user()->id)->update(['logged_out_at' => Carbon::now()]);
        return response()->json(['data' => ['status' => 'sucess']]);
    }

    public function update(Request $request){

        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.email' => [],
            'data.name' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.current_password' => [],
            'data.type' => [],
        ]);

        $data = $validated_data['data'];

        if(!key_exists('type',$data)){
            return response()->json(['data' => ['error' => 'Type required.']], 422);
        }

        $type = $data['type'];
        $value = $data[ $data['type'] ];

        if($type == 'password'){
            $type = 'edit_password';
        }

        $error = $this->validate_field($data,$type,$request->user()->id);
        if($error){
            return $error;
        }

        if($type == 'edit_password'){
           $value  = Hash::make($value);
        }

        User::where('id',$user_id)->update([$data['type'] => $value ]);

        return response()->json(['data' => ['status' => 'sucess']]);

    }

}
