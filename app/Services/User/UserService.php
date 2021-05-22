<?php

namespace App\Services\User;

use App\Models\FavouriteProducts;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\MonitoredProduct;
use App\Models\Review;
use App\Models\StoreType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class UserService extends UserAuthService {

    public function login($data): ?User{

        $user = User::where('email', trim($data['email']))->get()->first();

        if(!$user){
            throw new Exception('Email address doesn\'t belongs to any user. Please create a new account.', 404);
        }

        if (Hash::check($data['password'], $user->password)) {
            return $user;
        } else {

            if(!is_null($user->identifier)){
                throw new Exception('Your account is connected to Apple. Use the Apple button to log in.', 422);
            } else {
                throw new Exception('Incorrect password.', 403);
            }
            
        }

    }

    public function register($data): ?User {

        $identifier = $data['identifier'] ?? '';
        $user_token = $data['user_token'] ?? '';
        $notification_token = $data['notification_token'] ?? null;
        $store_type_id = $data['store_type_id'];
        
        $user_data = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'store_type_id' => $store_type_id,
            'password' => Hash::make($data['password']),
            'notification_token' => $data['notification_token']
        ];

        $user = User::where('email', $data['email'])->get()->first();
        $userExists = !is_null($user);

        if( $identifier != "" && $user_token != ""){
            // Apple Login

            if(!$this->validate_apple_login($data)){
                throw new Exception('Invalid user data provided.', 422);
            } else {
                $apple_user = User::where('identifier', $identifier)->get()->first();

                // User Found In Database. Or user exists with that email.
                if( !is_null($apple_user) || $userExists){
                    return $apple_user ?? $user;
                } else {
                    $user_data['identifier'] = $identifier;
                }

            }

        }

        if($userExists){
            throw new Exception('Email address belongs to another user.', 422);
        }

        // If duplicate notification token found. Remove other notification token from user.
        if(!is_null($notification_token) && User::where('notification_token', $notification_token)->exists() ){
            User::where('notification_token', $notification_token)->update(['notification_token' => null]);
        }

        $user = User::create($user_data);

        return $user;

    }

    public function delete($user){
        if(StoreType::where('user_id', $user->id)->exists()){
            throw new Exception('Failed to delete store account.', 402);
        }

        $reviews = Review::where('user_id', $user->id)->join('products', 'products.id', 'reviews.product_id')->groupBy('products.store_type_id')->get();

        foreach($reviews as $review){
            Review::where('user_id', $user->id)->update(['user_id' => $review->store_type_id]);
        }

        $lists = GroceryList::where('user_id', $user->id)->get();

        foreach($lists as $list){
            GroceryListItem::where('list_id', $list->id)->delete();
            $list->delete();
        }
        
        FavouriteProducts::where('user_id', $user->id)->delete();
        MonitoredProduct::where('user_id', $user->id)->delete();

        User::where('id', $user->id)->delete();
    }

    public function update($data, $user_id){
        $type = $data['type'];
        $value = $data[ $data['type'] ];

        if($type == 'password'){
            $type = 'edit_password';
        }

        if($type == 'send_notifications'){
            $data['send_notifications'] = (bool)$data['send_notifications'] ?? null;
        }

        $error = $this->validate_field($data,$type, $user_id);
        if($error){
            return $error;
        }

        if($type == 'edit_password'){
           $value  = Hash::make($value);
        }

        $update_fields = [ $data['type'] => $value ];

        if($type == 'send_notifications'){
            $update_fields['notification_token'] = $data['notification_token'] ?? null;
        }

        if($type == 'email'){
           if(User::where('id', '!=', $user_id)->where('email',$value)->exists()){
            throw new Exception('Email used by another user.', 422);
           }
        }

        User::where('id',$user_id)->update($update_fields);
    }

}
?>