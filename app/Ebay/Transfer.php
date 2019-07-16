<?php

namespace App\Ebay;

use GuzzleHttp\Client;

trait Transfer
{
    protected function message($method,$endpoint,array $param)
    {
        $guzzle = new Client([
            'base_uri' => self::API_URL
        ]);

//        return (string) ($guzzle->request($method,$endpoint,$param))->getBody();

        return json_decode(
            ($guzzle->request($method,$endpoint,$param))->getBody()
        );
    }
}