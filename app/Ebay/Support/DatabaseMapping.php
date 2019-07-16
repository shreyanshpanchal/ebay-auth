<?php

namespace App\Ebay\Support;

class DatabaseMapping
{
    public static function EbayOrder($order)
    {
        return [
            'orderId' => $order->orderId,
            'orderFulfillmentStatus' => $order->orderFulfillmentStatus,
            'orderPaymentStatus' => $order->orderPaymentStatus,
            'buyerCheckoutNotes' => $order->buyerCheckoutNotes,
            'buyer_username' => $order->buyer->username,
            'fulfillmentType' => $order->fulfillmentStartInstructions[0]->fulfillmentInstructionsType,
            'ebaySupportedFulfillment' => $order->fulfillmentStartInstructions[0]->ebaySupportedFulfillment,

            'totalDueSeller' => $order->paymentSummary->totalDueSeller->value,
            'currency' => $order->paymentSummary->totalDueSeller->currency,
            'refunds' => self::extractFromArray($order->paymentSummary->toArray(),'refunds'),

            'shippingCarrierCode' => $order->fulfillmentStartInstructions[0]->shippingStep->shippingCarrierCode,
            'shippingServiceCode' => $order->fulfillmentStartInstructions[0]->shippingStep->shippingServiceCode,
            'shipTo' => serialize($order->fulfillmentStartInstructions[0]->shippingStep->shipTo->toArray()),
            'creationDate' => $order->creationDate,
            'lastModifiedDate' => $order->lastModifiedDate
        ];
    }

    public static function EbayOrderCancellation($order)
    {
        return [
            'requests' => json_encode(self::extractFromArray($order->cancelStatus->toArray(),'cancelRequests')),
            'status' => $order->cancelStatus->cancelState,
            'cancelledDate' => $order->cancelStatus->cancelledDate
        ];
    }

    public static function EbayOrderPayment($order,$payment)
    {
        return [
            'orderId' => $order->orderId,
            'paymentReferenceId' => $payment->paymentReferenceId,
            'amount' => $payment->amount->value,
            'paymentMethod' => $payment->paymentMethod,
            'paymentStatus' => $payment->paymentStatus,
            'paymentHolds' => self::extractFromArray($payment->toArray(),'paymentHolds'),
            'paymentDate' => $payment->paymentDate
        ];
    }

    public static function EbayOrderLineitem($order,$lineItem)
    {
        return [
            'orderId' => $order->orderId,
            'legacyItemId' => $lineItem->legacyItemId,
            'legacyVariationId' => $lineItem->legacyVariationId,
            'lineItemId' => $lineItem->lineItemId,
            'title' => $lineItem->title,
            'quantity' => $lineItem->quantity,
            'soldFormat' => $lineItem->soldFormat,
            'listingvsPurchaseMarket' => $lineItem->listingMarketplaceId . '/' . $lineItem->purchaseMarketplaceId,
            'lineItemFulfillmentStatus' => $lineItem->lineItemFulfillmentStatus,
            'amount' => $lineItem->lineItemCost->value,
            'deliveryCost' => serialize($lineItem->deliveryCost->toArray()),
            'appliedPromotions' => self::extractFromArray($lineItem->toArray(),'appliedPromotions'),
            'taxes' => self::extractFromArray($lineItem->toArray(),'taxes'),
            'properties' => self::extractFromArray($lineItem->toArray(),'properties'),
            'shipByDate' => $lineItem->lineItemFulfillmentInstructions->shipByDate,
            'guaranteedDelivery' => $lineItem->lineItemFulfillmentInstructions->guaranteedDelivery
        ];
    }

    public static function EbayProduct($item)
    {
        return [
            'ItemID' => $item->ItemID,
            'BuyItNowPrice' => $item->BuyItNowPrice->value > 0 ? $item->BuyItNowPrice->value : $item->StartPrice->value,
            'Currency' => $item->BuyItNowPrice->value > 0 ? $item->BuyItNowPrice->currencyID : $item->StartPrice->currencyID,

            'StartTime' => $item->ListingDetails->StartTime,
            'ItemURL' => $item->ListingDetails->ViewItemURL,
            'ItemAffiliateURL' => $item->ListingDetails->ViewItemURLForNaturalSearch,

            'Description' => $item->Description ?? null,

            'ListingDuration' => $item->ListingDuration,
            'ListingType' => $item->ListingType,
            'Quantity' => $item->Quantity,

            'SellingStatus' => serialize($item->SellingStatus->toArray()),
            'ShippingDetails' => serialize($item->ShippingDetails->toArray()),

            'TimeLeft' => $item->TimeLeft,
            'Title' => $item->Title,
            'WatchCount' => $item->WatchCount,
            'QuestionCount' => $item->QuestionCount,
            'QuantityAvailable' => $item->QuantityAvailable ?? 0,

            'PictureDetails' => serialize($item->PictureDetails->toArray()),
            'NewLeadCount' => $item->NewLeadCount,

            'ClassifiedAdPayPerLeadFee' => $item->ClassifiedAdPayPerLeadFee->value ?? null,

            'ShippingProfileName' => !isset($item->SellerProfiles->SellerShippingProfile) ?: serialize($item->SellerProfiles->SellerShippingProfile->toArray()),
            'ReturnProfileName' => !isset($item->SellerProfiles->SellerReturnProfile) ?: serialize($item->SellerProfiles->SellerReturnProfile->toArray()),
            'PaymentProfileName' => !isset($item->SellerProfiles->SellerPaymentProfile) ?: serialize($item->SellerProfiles->SellerPaymentProfile->toArray())
        ];
    }

    public static function EbayProductVariant($EbayProduct,$variation)
    {
        return [
            'parent_id' => $EbayProduct->id,
            'StartPrice' => $variation->StartPrice->value,
            'Quantity' => $variation->Quantity,

            'QuantitySold' => $variation->SellingStatus->QuantitySold,
            'VariationTitle' => $variation->VariationTitle
        ];
    }

    /** Convert those values which can't be converted using 'toArray' using their parents & extract child's value from that. Yeah its fun.
     * @param array $array
     * @param string $key
     * @return array
     */
    private static function extractFromArray($array, $key)
    {
        return array_key_exists($key,$array) ? serialize(($array)[$key]) : null;
    }

}