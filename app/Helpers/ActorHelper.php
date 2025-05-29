<?php 

namespace App\Helpers;

class ActorHelper {

    /** 
     * Retrieve user ID from authenticated user and return.
     */
    public static function getUserId(){
        // Implement logic to fetch user ID from authenticated actor
        // Return the user ID as a string
        return request()->user()->id ?? 1;
    }


}