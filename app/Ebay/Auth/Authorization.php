<?php

namespace App\Ebay\Auth;

use App\Ebay\Transfer;
use Illuminate\Support\Facades\Cache;

class Authorization
{
    use Transfer;

    const API_URL = 'https://api.ebay.com/identity/v1/oauth2/token';

    public function generateUserAccessToken()
    {
        $token = $this->message('POST','', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Basic ". base64_encode(config('ebay.app_id').':'.config('ebay.app_secret'))
            ],

            'query' => [
                'grant_type' => 'authorization_code',
                'code' => $this->getAppAccessToken(),
                'redirect_uri' => config('ebay.app_runame')
            ]
        ]);

        return json_encode($token);
    }

    public function tokenFromRefreshToken($token)
    {
        $refreshedToken = $this->message('POST', '', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Basic ". base64_encode(config('ebay.app_id').':'.config('ebay.app_secret'))
            ],

            'query' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token,
                /*'scope'*/
            ]
        ]);

        return json_encode($refreshedToken);
    }

    public function getAppAccessToken()
    {
        return Cache::get('appAccessToken')['code'];
    }
}