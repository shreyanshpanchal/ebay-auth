<?php

namespace App\Ebay\Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Ebay\Transfer;

class Authorization
{
    use Transfer;

    const API_URL = 'https://api.ebay.com/identity/v1/oauth2/token';
    const TOKEN_DIR = 'ebay-auth/';
    const USER_TOKEN_FILE = '.user_access_token';
    const APP_TOKEN_FILE = '.application_token';

    public function __invoke($app_token = null)
    {
        return ($this->token($app_token))->access_token;
    }

    public function token()
    {
        return Storage::exists(self::TOKEN_DIR.self::USER_TOKEN_FILE) == true ?  $this->getSavedToken() : $this->generateUserAccessToken();
    }

    public function getSavedToken()
    {
        $token = unserialize( Storage::get(self::TOKEN_DIR.self::USER_TOKEN_FILE) );
        return $this->getValidToken($token);
    }

    public function getValidToken($token)
    {
        if($token->token_expire_at >= Carbon::now())
            return $token;

        elseif($token->refresh_token_expire_at >= Carbon::now())
            return $this->tokenFromRefreshToken($token);

        else
            return $this->generateUserAccessToken();
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
                'refresh_token' => $token->refresh_token,
                /*'scope'*/
            ]
        ]);

        $token->access_token = $refreshedToken->access_token;
        $token->token_expire_at = Carbon::now()->addSecond($refreshedToken->expires_in);

        Storage::put(self::TOKEN_DIR.self::USER_TOKEN_FILE, serialize($token) );

        return $token;
    }

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

        $token->token_expire_at = Carbon::now()->addSecond($token->expires_in);
        $token->refresh_token_expire_at = Carbon::now()->addSecond($token->refresh_token_expires_in);

        Storage::put(self::TOKEN_DIR.self::USER_TOKEN_FILE, serialize($token) );

        return $token;
    }

    public function saveAppAccessToken($token)
    {
        $token = array_merge($token,['expire_at' => Carbon::now()->addSecond($token['expires_in']) ]);

        return Storage::put(self::TOKEN_DIR.self::APP_TOKEN_FILE, serialize($token) );
    }

    public function getAppAccessToken()
    {
        if(Storage::exists(self::TOKEN_DIR.self::APP_TOKEN_FILE) == true)
        {
            $app_token = unserialize( Storage::get(self::TOKEN_DIR.self::APP_TOKEN_FILE) );

            if($app_token['expire_at'] >= Carbon::now())
                return $app_token['code'];
            else
                throw new AppTokenNotFoundException('Expired Application Token.');
        }
        else
        {
            throw new AppTokenNotFoundException('Application Token Not Found.');
        }
    }

    # We'll use this to determine if user has linked ebay account or not.
    public static function AppTokenExists()
    {
        return Storage::exists(self::TOKEN_DIR.self::APP_TOKEN_FILE);
    }
}