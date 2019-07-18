<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ebay\Auth\Authorization;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use App\Jobs\PostTokens;

class EbayAuthController extends Controller
{
    public function step1(Request $request)
    {
        Cache::rememberForever('callback',$request->input('callback'));

        return redirect()->away( config('ebay.branded_signin') );
    }

    public function step2(Request $request)
    {
        Cache::put('appAccessToken', $request->all(), $request->input('expires_in'));
        
        PostTokens::dispatchNow();

        return 'Authentication Successful, Please close this window';
    }

    public function refreshToken($refresh, Authorization $auth)
    {
        return $auth->tokenFromRefreshToken($refresh);
    }
}
