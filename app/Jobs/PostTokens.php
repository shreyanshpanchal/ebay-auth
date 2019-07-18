<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Ebay\Auth\Authorization;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class PostTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * @param Authorization $auth
     * @return void
     * @throws
     */
    public function handle(Authorization $auth)
    {
        $token = $auth->generateUserAccessToken();

        $guzzle = new Client([
            'base_uri' => Cache::get('callback')
        ]);

        $guzzle->request('POST','',['payload' => $token]);

        return response('success',200);
    }
}
