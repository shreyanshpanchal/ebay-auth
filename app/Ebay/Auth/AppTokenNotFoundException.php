<?php

namespace App\Ebay\Auth;

use Exception;

class AppTokenNotFoundException extends Exception
{
    public function render()
    {
        return redirect()->away( config('ebay.branded_signin') );
    }
}