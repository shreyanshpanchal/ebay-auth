<?php

namespace App\Ebay\Support;

use Illuminate\Support\Facades\Cache;

class Schedule
{
    public static function getAll()
    {
        return Cache::get('ebayConfig') ?? [];
    }

    public static function getMinAmountForTracking()
    {
        return Cache::get('ebayConfig')['minAmountForTracking']['value'] ?? 20;
    }

    public static function getFrequencyByKey($key)
    {
        $frequencyByKey = Cache::get('ebayConfig')[$key]['value'] ?? null;

        if(is_array($frequencyByKey['frequency']))
        {
            $frequency = head(array_keys($frequencyByKey['frequency']));
            $frequency_parameter = $frequencyByKey['frequency'];
        }
        else
        {
            $frequency = $frequencyByKey['frequency'];
            $frequency_parameter = [];
        }

        if(is_array($frequencyByKey['exemption']))
        {
            $exemption = head(array_keys($frequencyByKey['exemption']));
            $exemption_parameter = $frequencyByKey['exemption'];
        }
        else
        {
            $exemption = $frequencyByKey['exemption'];
            $exemption_parameter = [];
        }

        return compact( 'frequency', 'frequency_parameter', 'exemption', 'exemption_parameter');
    }

    public static function getShippingCode()
    {
        $shippingServiceCode = Cache::rememberForever('shippingServiceCode', function ()  {

            $shippingCode = app('Ebay\Trading')->GetEbayDetails(['ShippingServiceDetails'])->toArray();

            return array_map(function ($value){
                return $value['ShippingService'];
            }, $shippingCode["ShippingServiceDetails"]);

        });

        $shippingCarrierCode = Cache::rememberForever('shippingCarrierCode', function () {

            $shippingCode = app('Ebay\Trading')->GetEbayDetails(['ShippingCarrierDetails'])->toArray();

            return array_map(function ($value){
                return $value['Description'];
            }, $shippingCode["ShippingCarrierDetails"]);

        });

        return ['shippingServiceCode' => $shippingServiceCode, 'shippingCarrierCode' => $shippingCarrierCode];
    }

    public static function scheduleCommandRun($schedule, $command, $key)
    {
        $config = self::getFrequencyByKey($key);

        $frequency = $config['frequency'] ?? 'hourly';

        $frequency_parameter = ( empty($config['frequency_parameter']) )?'':head($config['frequency_parameter']);

        $exemption = $config['exemption'];

        if(is_array($frequency_parameter))
        {
            if($exemption)
            {
                if(empty($config['exemption_parameter']))
                {
                    $schedule->command($command)
                        ->$frequency($frequency_parameter['from'], $frequency_parameter['to'])
                        ->$exemption();
                }
                else
                {
                    $from = head( $config['exemption_parameter'] )['from'];
                    $to = head( $config['exemption_parameter'] )['to'];

                    $schedule->command($command)
                        ->$frequency($frequency_parameter['from'], $frequency_parameter['to'])
                        ->$exemption($from, $to);
                }
            }
            else
            {
                $schedule->command($command)->$frequency($frequency_parameter['from'], $frequency_parameter['to']);
            }
        }
        else
        {
            if($exemption)
            {
                if(empty($config['exemption_parameter']))
                {
                    $schedule->command($command)
                        ->$frequency($frequency_parameter)
                        ->$exemption();
                }
                else
                {
                    $from = head( $config['exemption_parameter'] )['from'];
                    $to = head( $config['exemption_parameter'] )['to'];

                    $schedule->command($command)
                        ->$frequency($frequency_parameter)
                        ->$exemption($from, $to);
                }
            }
            else
            {
                $schedule->command($command)->$frequency($frequency_parameter);
            }
        }
    }
}