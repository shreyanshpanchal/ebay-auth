<?php

namespace App\Ebay\Support;

use App\EbayOrder;
use App\EbayOrderLineitem;
use App\EbayOrderPayment;
use App\EbayOrderCancel;
use DTS\eBaySDK\Fulfillment\Enums\OrderFulfillmentStatus;
use Illuminate\Support\Facades\DB;

class OrderFulfillment
{
    private $fulfillment;

    public function __construct()
    {
        $this->fulfillment = app('Ebay\Fulfillment');
    }

    public function DownloadOrders()
    {
        $filter = $this->applyFilters(
            optional(EbayOrder::select('creationDate')->latest('creationDate')->first())->creationDate
        );

        $this->download($filter);
    }

    public function DownloadAllOrders()
    {
        $this->download("orderfulfillmentstatus:{NOT_STARTED|IN_PROGRESS}");
    }

    /** Download single order by id
     * @param array $orderIds
     */
    public function DownloadOrderByID(array $orderIds)
    {
        foreach(array_chunk($orderIds,50) as $batch)
        {
            $orders = $this->fulfillment->GetOrdersById($batch);

            $this->save($orders);
        }
    }

    /** Update orders by id
     * @param array $orderIds
     * @return boolean
     */
    public function UpdateOrderByID(array $orderIds)
    {
        foreach(array_chunk($orderIds,50) as $batch)
        {
            $orders = $this->fulfillment->GetOrdersById($batch);

            $this->update($orders);
        }

        return true;
    }

    /** Pass Carbon date to set as creation date filter, Otherwise orders since last 7 days.
     * @param Carbon\Carbon $lastOrderDate
     * @return string
     */
    private function applyFilters($lastOrderDate=null)
    {
        $lastOrderDate = $lastOrderDate ?? now()->setTimezone('UTC')->subDays(30)->format('Y-m-d\TH:i:s.v\Z');

        $filter = "orderfulfillmentstatus:{NOT_STARTED|IN_PROGRESS}";

        $filter .= ",creationdate:[" . $lastOrderDate . "]";

        return $filter;
    }

    /** Recursively download orders based on $filter
     * @param string $filter
     */
    public function download($filter=null)
    {
        $offset = 0;

        do {
            $orders = $this->fulfillment->GetOrders($offset, $filter);

            $this->save($orders);

            $offset++;

        } while ($orders->next != null);
    }

    /** Save orders in database
     * @param DTS\eBaySDK\Fulfillment\Types\Order
     */
    private function save($orders)
    {
        foreach($orders->orders as $order)
        {
            if(EbayOrder::where('orderId',$order->orderId)->exists())
                continue;

            // Remember to not wrap DatabaseMapping in array.
            $EbayOrder = EbayOrder::create(
                DatabaseMapping::EbayOrder($order)
            );

            # Save Raw Data
            DB::table('ebay_orders_raw')->insert([
                'orderId' => $order->orderId,
                'data' => json_encode($order->toArray())
            ]);

            if ($order->cancelStatus->cancelState != "NONE_REQUESTED") {
                $EbayOrder->cancellation()->create(
                    DatabaseMapping::EbayOrderCancellation($order)
                );
            }

            foreach ($order->paymentSummary->payments as $payment) {
                EbayOrderPayment::create(
                    DatabaseMapping::EbayOrderPayment($order, $payment)
                );
            }

            foreach ($order->lineItems as $lineItem) {
                EbayOrderLineitem::create(
                    DatabaseMapping::EbayOrderLineitem($order, $lineItem)
                );
            }
        }
    }

    private function update($orders)
    {
        foreach($orders->orders as $order)
        {
            if($this->isDispatchedButNotFulfilled($order))
                continue;

            EbayOrder::where('orderId', $order->orderId)->update(
                DatabaseMapping::EbayOrder($order)
            );

            # Save Raw Data
            DB::table('ebay_orders_raw')
                ->where('orderId', $order->orderId)
                ->update(['data' => json_encode($order->toArray()) ]);

            if ($order->cancelStatus->cancelState != "NONE_REQUESTED") {
                EbayOrderCancel::updateOrCreate(
                    ['orderId' => $order->orderId],
                    DatabaseMapping::EbayOrderCancellation($order)
                );
            }

            foreach ($order->paymentSummary->payments as $payment) {
                EbayOrderPayment::where('paymentReferenceId', $payment->paymentReferenceId)->update(
                    DatabaseMapping::EbayOrderPayment($order, $payment)
                );
            }

            foreach ($order->lineItems as $lineItem) {
                EbayOrderLineitem::where('lineItemId', $lineItem->lineItemId)->update(
                    DatabaseMapping::EbayOrderLineitem($order, $lineItem)
                );
            }
        }
    }

    /**
     * @param $order
     *
     * @return bool
     */
    private function isDispatchedButNotFulfilled($order): bool
    {
        return in_array(EbayOrder::where('orderId', $order->orderId)->first()->orderFulfillmentStatus, ['MARKED_FOR_DISPATCH', 'DISPATCHED']) && $order->orderFulfillmentStatus == "NOT_STARTED";
    }
}