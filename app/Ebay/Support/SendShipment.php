<?php

namespace App\Ebay\Support;

use App\EbayOrder;
use App\EbayOrderLineitem;

class SendShipment
{
    public function send()
    {
        $lineitems = EbayOrderLineitem::marked()->get();

        $packages = array();

        foreach ($lineitems as $lineitem)
        {
            if ($itemFulfillment = $lineitem->fulfillment)
                $this->withTracking($lineitem, $packages, $itemFulfillment);
            else
                $this->withoutTracking($lineitem, $packages);
        }

        $this->sendFulfillmentPayload($packages);
    }

    private function withTracking($lineitem, &$fulfillments, $itemFulfillment)
    {
        if(!isset( $fulfillments[$lineitem->orderId] ))
            $fulfillments[$lineitem->orderId] = array();

        if(!isset( $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber] ))
            $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber] = array();

        if(!isset( $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber]['lineitems'] ))
            $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber]['lineitems'] = array();

        $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber]['shippingCarrierCode'] = $itemFulfillment->shippingCarrierCode;
        $fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber]['trackingNumber'] = $itemFulfillment->trackingNumber;

        array_push($fulfillments[$lineitem->orderId][$itemFulfillment->trackingNumber]['lineitems'], [
            'legacyItemId' => $lineitem->legacyItemId, // Solely for stock deduction
            'lineItemId' => $itemFulfillment->lineItemId,
            'quantity' => $itemFulfillment->quantity,
        ]);
    }

    private function withoutTracking($lineitem, &$fulfillments)
    {
        if(!isset( $fulfillments[$lineitem->orderId] )) # in_array doesn't work on multidimensional array.
            $fulfillments[$lineitem->orderId] = array();

        if(!isset( $fulfillments[$lineitem->orderId][$lineitem->legacyItemId]['lineitems'] )) # in_array doesn't work on multidimensional array.
            $fulfillments[$lineitem->orderId][$lineitem->legacyItemId]['lineitems'] = array();

        array_push($fulfillments[$lineitem->orderId][$lineitem->legacyItemId]['lineitems'],[
            'legacyItemId' => $lineitem->legacyItemId, // Solely for stock deduction
            'lineItemId' => $lineitem->lineItemId,
            'quantity' => $lineitem->quantity
        ]);
    }

    private function sendFulfillmentPayload(array $fulfillments)
    {
        foreach($fulfillments as $orderId => $fulfillment)
        {
            foreach ($fulfillment as $package)
            {
                $operation = app('Ebay\Fulfillment')->CreateShippingFulfillment($orderId, $package);

                # Status code for created
                if($operation->getStatusCode() == 201)
                {
                    EbayOrder::where('orderId',$orderId)->update(['orderFulfillmentStatus' => 'DISPATCHED']);

                    EbayOrderLineitem::where('orderId',$orderId)->update(['lineItemFulfillmentStatus' => 'DISPATCHED']);
                }
                else
                {
                    throw new ShipmentNotAccepted("OrderID:{$orderId}; Package:" . json_encode($package) . "; Status:" . $operation->getStatusCode());
                }
            }
        }
    }
}
