<?php

namespace App\Ebay;

use App\Ebay\Auth\Authorization;
use Carbon\Carbon;
use DTS\eBaySDK\HalfFinding\Enums\Type;
use DTS\eBaySDK\Trading\Services\TradingService;
use DTS\eBaySDK\Trading\Types;

/*
 * Rename this to: TradingAccess
 */
class Trading
{
    protected $service;

    public function __construct(Authorization $authorization)
    {
        $this->service = new TradingService([
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

    public function GetStore()
    {
        $request = new Types\GetStoreRequestType();

        return $this->service->getStore($request);
    }

    public function GetSellerDashboard()
    {
        $request = new Types\GetSellerDashboardRequestType();

        return $this->service->getSellerDashboard($request);
    }

    public function GetSellerEvents()
    {
        $request = new Types\GetSellerEventsRequestType();

        return $this->service->getSellerEvents($request);
    }

    public function GetItem($item_id)
    {
        $request = new Types\GetItemRequestType();

        $request->ItemID = $item_id ?? $item_id;

        return $this->service->getItem($request);
    }

    public function GetAccount()
    {
        $request = new Types\GetAccountRequestType();

        return $this->service->getAccount($request);
    }

    public function GetMyEbaySelling($pgnumber=1)
    {
        $request = new Types\GetMyeBaySellingRequestType();

        $request->ActiveList = new Types\ItemListCustomizationType();

        $request->ActiveList->Include = true;

        $request->ActiveList->Pagination = new Types\PaginationType();

        $request->ActiveList->Pagination->PageNumber = $pgnumber;

        return $this->service->getMyeBaySelling($request);
    }

    public function GetSellerList($pgnumber=1, $startTimeFrom)
    {
        $request = new Types\GetSellerListRequestType();

        $request->IncludeVariations = true;

        $request->StartTimeFrom = $startTimeFrom;
        $request->StartTimeTo = date_create(Carbon::now()->toIso8601ZuluString());

        $request->DetailLevel[] = "ReturnAll";

        $request->Pagination = new Types\PaginationType;

        #Default limit
        $request->Pagination->EntriesPerPage=25;

        $request->Pagination->PageNumber=$pgnumber;

        return $this->service->getSellerList($request);
    }

    public function GetSellerListByEnd($pgnumber=1, $endTimeFrom)
    {
        $request = new Types\GetSellerListRequestType();

        $request->IncludeVariations = true;

        $request->EndTimeFrom = date_create(Carbon::now()->toIso8601ZuluString());
        $request->EndTimeTo = $endTimeFrom;

        $request->DetailLevel[] = "ReturnAll";

        $request->Pagination = new Types\PaginationType;

        #Default limit
        $request->Pagination->EntriesPerPage=25;

        $request->Pagination->PageNumber=$pgnumber;

        return $this->service->getSellerList($request);
    }

    public function GetEbayDetails(Array $detailName)
    {
        $request = new Types\GeteBayDetailsRequestType();

        $request->DetailName = $detailName;

        return $this->service->geteBayDetails($request);
    }

    public function GetOrders(array $orders)
    {
        $request = new Types\GetOrdersRequestType();

        $arr = [];

        foreach($orders as $order)
        {
            array_push($arr, $order);
        }

        $request->OrderIDArray = new Types\OrderIDArrayType();

        $request->OrderIDArray->OrderID = $arr;
        /*$request->CreateTimeFrom = date_create(\Carbon\Carbon::create(2018,10,12)->toIso8601ZuluString());
        $request->CreateTimeTo = date_create(Carbon::now()->subDays(30)->toIso8601ZuluString());
        $request->OrderStatus = "Active";*/
        return $this->service->getOrders($request);
    }

    public function GetAnOrder($order)
    {
        $request = new Types\GetOrdersRequestType();

        $request->OrderIDArray = new Types\OrderIDArrayType();
        $request->OrderIDArray->OrderID[] = $order;

        return $this->service->getOrders($request);
    }

    public function GetMemberMessages()
    {
        $request = new Types\GetMemberMessagesRequestType();

        $request->MailMessageType = 'All';

        $request->StartCreationTime = date_create(\Carbon\Carbon::now()->subDays(1)->toIso8601ZuluString());
        $request->EndCreationTime = date_create(\Carbon\Carbon::now()->toIso8601ZuluString());

        return $this->service->getMemberMessages($request);
    }

    public function GetMyMessages($detailLevel, $start_time, $folder=0, $pgnumber=1)
    {
        $request = new Types\GetMyMessagesRequestType();

        $request->DetailLevel[] = $detailLevel; // 'ReturnHeaders', 'ReturnSummary', 'ReturnMessages'

        $request->FolderID = (int) $folder;

        $request->StartTime = $start_time;

        $request->EndTime = date_create(\Carbon\Carbon::now()->toIso8601ZuluString());

        $request->Pagination = new Types\PaginationType();
        $request->Pagination->PageNumber = $pgnumber;
        $request->Pagination->EntriesPerPage = 200;

        return $this->service->getMyMessages($request);
    }

    public function GetMessageById($message_ids)
    {
        $request = new Types\GetMyMessagesRequestType();

        $request->DetailLevel[] = 'ReturnMessages';

        $request->MessageIDs = new Types\MyMessagesMessageIDArrayType();

        $request->MessageIDs->MessageID = $message_ids;

        return $this->service->getMyMessages($request);
    }

    public function ReviseMyMessages($MessageID)
    {
        $request = new Types\ReviseMyMessagesRequestType();
        $request->FolderID = 0;
        $request->Read = true;
        $request->MessageIDs = new Types\MyMessagesMessageIDArrayType();
        $request->MessageIDs->MessageID = $MessageID;

        return $this->service->reviseMyMessages($request);
    }

    public function ReviseSellingManagerSaleRecord($orderid, $address)
    {
        $request = new Types\ReviseSellingManagerSaleRecordRequestType();

        $request->OrderID = $orderid;

        $request->SellingManagerSoldOrder = new Types\SellingManagerSoldOrderType;

        $request->SellingManagerSoldOrder->ShippingAddress = new Types\AddressType;

        $request->SellingManagerSoldOrder->ShippingAddress->Name = '';

        return $this->service->reviseSellingManagerSaleRecord($request);
    }

    public function GetApiAccessRules()
    {
        $request = new Types\GetApiAccessRulesRequestType();

        return $this->service->getApiAccessRules($request);
    }
}