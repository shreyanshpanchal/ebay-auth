<?php

namespace App\Ebay\Support;

use App\EbayMessage;
use App\EbayMessageFolder;
use Illuminate\Support\Facades\Cache;

class Message
{
    const FOLDER_MAP = [
        0 => 'Inbox',
        1 => 'Sent',
        6 => 'Archive'
    ];

    public function download()
    {
        if(EbayMessageFolder::count() >= 1)
            $start_time = date_create(EbayMessageFolder::select('created_at')->latest()->first()->created_at->toIso8601ZuluString());
        else
            $start_time = date_create(\Carbon\Carbon::now()->subDays(7)->toIso8601ZuluString());

        $this->downloadFoldersList($start_time);

        $this->downloadMessages($start_time);

        $this->downloadMessagesContent();
    }

    public function downloadFoldersList($start_time)
    {
        $folders = app('Ebay\Trading')->GetMyMessages('ReturnSummary', $start_time);

        # Temp patch: But make sure to code an exception for this.
        if(!isset($folders->Summary))
            exit;

        foreach ($folders->Summary->FolderSummary as $folder)
        {
            if(array_key_exists($folder->FolderID,self::FOLDER_MAP))
                $folder_name_static = self::FOLDER_MAP[$folder->FolderID];
            else
                $folder_name_static = 'Folder ' . $folder->FolderID;

            EbayMessageFolder::updateOrCreate(
                ['folder_id' => $folder->FolderID],
                [
                    'folder_name' => $folder->FolderName ?? $folder_name_static,
                    'new_messages' => $folder->NewMessageCount,
                    'total_messages' => $folder->TotalMessageCount
                ]
            );
        }
    }

    public function downloadMessages($start_time)
    {
        foreach(EbayMessageFolder::get() as $folder)
        {
            for ($pgnumber = 1; $pgnumber <= ceil($folder->total_messages / 200); $pgnumber++)
            {
                $messages = app('Ebay\Trading')->GetMyMessages('ReturnHeaders', $start_time, $folder->folder_id, $pgnumber);

                if( isset($messages->Messages->Message) )
                    $this->save($messages->Messages->Message);
            }
        }
    }

    public function downloadMessagesContent()
    {
        foreach(EbayMessage::select('message_id')->whereNull('text')->get()->chunk(10) as $message)
        {
            $content = app('Ebay\Trading')->GetMessageById($message->pluck('message_id')->toArray());

            foreach ($content->Messages->Message ?? [] as $message)
            {
                EbayMessage::where('message_id', $message->MessageID)->update(['text' => $message->Text]);
            }
        }
    }

    private function save($messages)
    {
        foreach ($messages as $message)
        {
            EbayMessage::firstOrCreate(['message_id' => $message->MessageID],
            [
                'item_id' => $message->ItemID ?? null,
                'item_title' => $message->ItemTitle ?? null,
                'folder_id' => $message->Folder->FolderID,
                'buyer_id' => $this->getBuyerId($message),

                'external_message_id' => $message->ExternalMessageID ?? null,
                'message_type' => $message->MessageType ?? null,
                'subject' => $message->Subject,
                'response_details' => $message->ResponseDetails->ResponseURL ?? null,
                'receive_date' => $message->ReceiveDate,
                'expiration_date' => $message->ExpirationDate,
                'read' => $message->Read,
                'replied' => $message->Replied,
                'message_media' => serialize($message->toArray()['MessageMedia'] ?? []),
            ]);
        }
    }

    private function getBuyerId($message)
    {
        if(isset($message->Sender))
            return $message->Sender;

        elseif(isset($message->SendToName))
            return $message->SendToName;

        elseif(isset($message->Sender))
            return $message->Sender;
    }

    public function markAsRead()
    {
        foreach ( collect($this->getMessagesID() )->chunk(10)->toArray() as $cacheMessageIds )
        {
            $response = app('Ebay\Trading')->ReviseMyMessages($cacheMessageIds);

            if($response->Ack == 'Success')
                Cache::forget('ebayMessageMarkAsRead');
        }
    }

    public function getMessagesID()
    {
        return Cache::get('ebayMessageMarkAsRead') ?? [];
    }

    public function setMessagesID($message)
    {
        $cache_message_id = $this->getMessagesID();

        Cache::rememberForever('ebayMessageMarkAsRead', function () use ($message, $cache_message_id) {
            $cache_message_id[$message->id] = $message->message_id;
            return $cache_message_id;
        });
    }
}