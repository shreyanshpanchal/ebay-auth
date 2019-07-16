<?php

namespace App\Ebay\Support;

use App\Company;
use App\SalesOrder;
use App\Storage;
use App\EbayProduct;
use App\EbayOrderLineitem;
use App\StockTransaction;

class OrderShipping
{
    /*
      MECHANISM EXPLAINED
        - Prepare Array in preferred format
        - Recursively upload package (POST to ebay)
        - create entry in sales orders.
        - Deduct quantities from related product
        - update order
    */

    public function shipItems(array $items)
    {
        $items = EbayOrderLineitem::find($items);

        $this->ship($items);
    }

    public function shipAllItems()
    {
        $items = EbayOrderLineitem::active()->get();

        $this->ship($items);
    }

    /*
     * @param \App\EbayOrderLineItem $items
     */
    private function ship($items)
    {
        $shipments = new SendShipment();

        $eligibleItems = $items->filter(function ($item) {

            if ($item->eligibleForDispatch)
            {
                $this->markForDispatch($item);

                $this->deductQuantity($item);

                return $item;
            }

        });

        #$shipments->send($eligibleItems);
    }

    /*
     * @param \App\EbayOrderLineItem $lineitem
     * @refactor If order has multiple items: This will run multiple times
     */
    private function markForDispatch($lineitem)
    {
        $lineitem->update(['lineItemFulfillmentStatus' => 'MARKED_FOR_DISPATCH']);

        $lineitem->ebayOrder->update(['orderFulfillmentStatus' => 'MARKED_FOR_DISPATCH']);
    }

    private function deductQuantity($lineitem)
    {
        # Create sales order entry
        SalesOrder::firstOrCreate(['ebay_lineitem' => $lineitem->lineItemId]);

        # Fetch relevant option->stock & deduct from default storage.
        $ebayProduct = EbayProduct::where('ItemID',$lineitem->legacyItemId)->first();

        if($ebayProduct != null && $ebayProduct->inventory()->exists() && $ebayProduct->inventory->first()->sales_stock != null )
        {
            $ebayProduct->inventory->first()->sales_stock->decrement('quantity', $lineitem->quantity);

            StockTransaction::create([
                'product_id' => $ebayProduct->inventory->first()->product_id,
                'variation' => serialize([
                    'variation_key' => 'Default',
                    'variation_value' => 'Default',
                    'storage_name' => optional(Company::first())->sales_default_storage ?? 1,
                ]),
                'action' => 'Decrement',
                'description' => "Ebay Sales: " . $lineitem->salesRecordNumber,
                'quantity' => $lineitem->quantity,
            ]);
        }
        elseif( $ebayProduct != null && $ebayProduct->variants()->exists() )
        {
            $ebayProduct = $ebayProduct
                ->load(['variants' => function ($query) use ($lineitem) {
                    $query->whereHas('namevalue', function ($constraint) use ($lineitem) {
                        foreach ($lineitem->variations as $orderVariation)
                        {
                            $constraint->where('name', $orderVariation->name);
                            $constraint->where('value', $orderVariation->value);
                        }
                    });
                }]);

            foreach ($ebayProduct->variants as $variant)
            {
                if ($variant->inventory()->exists() && $variant->inventory->first()->sales_stock != null)
                {
                    $variant->inventory->first()->sales_stock->decrement('quantity', $lineitem->quantity);

                    StockTransaction::create([
                        'product_id'  => $variant->inventory->first()->product_id,
                        'variation'   => serialize([
                            'variation_key'   => $variant->inventory->first()->option_key,
                            'variation_value' => $variant->inventory->first()->option_value,
                            'storage_name'    => optional(Storage::find(Company::first()->sales_default_storage))->name ?? 1,
                        ]),
                        'action'      => 'Decrement',
                        'description' => "Ebay Sales: " . $lineitem->salesRecordNumber,
                        'quantity'    => $lineitem->quantity,
                    ]);
                }
            }
        }
    }
}
