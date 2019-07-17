<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ebay\Auth\Authorization;
use Illuminate\Support\Facades\Cache;

class EbayAuthController extends Controller
{
    public function step1(Request $request)
    {
        return $request->getHost();
        return redirect()->away( config('ebay.branded_signin') );
    }

    public function step2(Request $request)
    {
        Cache::put('appAccessToken', $request->all(), $request->input('expires_in'));

        return redirect()->route('reply.token')->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function step3(Authorization $auth)
    {
        return $auth->generateUserAccessToken();
    }

    public function refreshToken($refresh, Authorization $auth)
    {
        return $auth->tokenFromRefreshToken($refresh);
    }
}
