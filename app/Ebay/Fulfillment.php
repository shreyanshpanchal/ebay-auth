<?php

namespace App\Ebay;

use App\Ebay\Auth\Authorization;
use DTS\eBaySDK\Fulfillment\Services\FulfillmentService;
use DTS\eBaySDK\Fulfillment\Types;
use DTS\eBaySDK\Fulfillment\Enums\OrderFulfillmentStatus;

class Fulfillment
{
    protected $service;

    public function __construct(Authorization $authorization)
    {
        $this->service = new FulfillmentService([
            'siteId' => 3,
            'marketplaceId' => \DTS\eBaySDK\Constants\GlobalIds::GB,
            'credentials' => [
                'appId'  => config('ebay.app_id'),
                'certId' => config('ebay.app_secret'),
                'devId'  => config('ebay.dev_id')
            ],
            'authorization' => $authorization()
        ]);
    }

    /** Get orders using filter
     * @param int $offset
     * @param string $filter
     * @param int $limit
     * @return Types\GetOrdersRestResponse
     */
    public function GetOrders($offset=0, $filter=null, $limit=1000)
    {
        $request = new Types\GetOrdersRestRequest();

        $request->offset = (string) $offset;

        $request->limit = (string) $limit;

        if($filter != null)
            $request->filter = $filter;

        return $this->service->getOrders($request);
    }


    /** Get orders by array of orderIds
     * @param array $orderIds
     * @throws \Exception
     * @return Types\GetOrdersRestResponse
     */
    public function GetOrdersById(array $orderIds)
    {
        $request = new Types\GetOrdersRestRequest();

        if(count($orderIds) > 50)
            throw new \Exception("GetOrders can only query 50 order at a time using orders filter");

        $request->orderIds = implode(',',$orderIds);

        $request->orderIds = trim($request->orderIds,',');

        return $this->service->getOrders($request);
    }

    /** Get single order
     * Flag: Deprecate
     * @param $orderId
     * @return Types\GetAnOrderRestResponse
     */
    public function GetAnOrder($orderId)
    {
        $request = new Types\GetAnOrderRestRequest();

        $request->orderId = $orderId;

        return $this->service->getAnOrder($request);
    }

    public function GetShippingFulfillments($orderId)
    {
        $request = new Types\GetShippingFulfillmentsRestRequest();

        $request->orderId = $orderId;

        return $this->service->getShippingFulfillments($request);
    }

    public function GetAShippingFulfillment($orderId, $fulfillmentId)
    {
        $request = new Types\GetAShippingFulfillmentRestRequest();

        $request->orderId = $orderId;
        $request->fulfillmentId = $fulfillmentId;

        return $this->service->getAShippingFulfillment($request);
    }

    public function CreateShippingFulfillment($orderId,$fulfillments)
    {
        $request = new Types\CreateAShippingFulfillmentRestRequest();

        $request->orderId = $orderId;

        $lineitems = [];

        foreach($fulfillments['lineitems'] as $lineitem)
        {
            # There's a scope to hydrate this.
            $item = new Types\LineItemReference();

            $item->lineItemId = $lineitem['lineItemId'];
            $item->quantity = (int) $lineitem['quantity'];

            array_push($lineitems,$item);
        }

        $request->lineItems = $lineitems;

        if(isset($fulfillments['shippingCarrierCode']))
            $request->shippingCarrierCode = $fulfillments['shippingCarrierCode'];

        if(isset($fulfillments['trackingNumber']))
             $request->trackingNumber = $fulfillments['trackingNumber'];

        return $this->service->createAShippingFulfillment($request);
    }
}