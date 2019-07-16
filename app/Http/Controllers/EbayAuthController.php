<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ebay\Auth\Authorization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class EbayAuthController extends Controller
{
    # returnToken & saveAppToken is for actually getting a valid token from ebay.
    # You don't have to manually use this routes & controller functions.
    public function getAuth(Authorization $authorization)
    {
        $authorization();

        return redirect()->route('ebay.home')->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function saveAppToken(Request $request,Authorization $ebyAuth)
    {
        $ebyAuth->saveAppAccessToken($request->all());

        return redirect()->route('ebay.auth.return.token')->header('Cache-Control', 'no-store, no-cache, must-revalidate');;
    }

    public function returnToken(Authorization $ebyAuth)
    {
        $ebyAuth->getAppAccessToken();

        return redirect()->route('ebay.home')->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function storeSummary()
    {
        if(Authorization::AppTokenExists())
        {
            # make an array of useful information then store it in cache, This one has serialization issue
            $store = Cache::rememberForever('storeSummary', function () {

                $response = app('Ebay\Trading')->GetStore();
                $sellerDashboard = app('Ebay\Trading')->GetSellerDashboard()->toArray();

                return array(
                    'name' => $response->Store->Name,
                    'url' => $response->Store->URL,
                    'subscriptionLevel' => $response->Store->SubscriptionLevel,
                    'sellerAccount' => $sellerDashboard['SellerAccount']['Status'],
                    'sellerFeeDiscount' => $sellerDashboard['SellerFeeDiscount']['Percent'],
                    'powerSellerStatus' => $sellerDashboard['PowerSellerStatus']['Level'],
                );
            });
        }
        else {
            $store = null;
        }
        
        return json_encode($store);
        // return view('ebay.home',compact('store'));
    }

    public function disconnect()
    {
        Storage::delete([
            Authorization::TOKEN_DIR.Authorization::USER_TOKEN_FILE,
            Authorization::TOKEN_DIR.Authorization::APP_TOKEN_FILE
        ]);
        Cache::forget('storeSummary');

        return redirect()->back();
    }
}
