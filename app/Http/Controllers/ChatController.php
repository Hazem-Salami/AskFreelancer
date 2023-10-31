<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Traits\ResponseTrait;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    use ResponseTrait;

    public function getRoomId($id)
    {
        $myRoom = ChatRoom::where('sender_id', auth()->user()->id)
            ->where('receiver_id', $id)
            ->orWhere('receiver_id', auth()->user()->id)
            ->where('sender_id', $id)
            ->first();

        if ($myRoom === null)
            return $this->failed('لا يوجد دردشة بينكما بعد، أبدأ الآن');
        return $this->success('الدردشة', $myRoom);
    }//end of function

    //getting my messages for a conversation
    public function index($id)
    {
        $myMessages = ChatRoom::
        join('messages', 'messages.room_id', '=', 'chat_rooms.id')
            ->where('sender_id', auth()->user()->id)
            ->where('receiver_id', $id)
            ->orWhere('receiver_id', auth()->user()->id)
            ->where('sender_id', $id)
            ->select('messages.created_at', 'messages.body', 'messages.user_id')
            ->orderBy('messages.created_at', 'desc')
            ->get();

        return $this->success('My messages', $myMessages);
    }//end of index

    public function sendMessage(Request $request, $id)
    {
        $validator = Validator::make($request->post(), [
            'body' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $room = ChatRoom::where('sender_id', auth()->user()->id)
                ->where('receiver_id', $id)->first();

            if ($room === null)
                $room = ChatRoom::create([
                    'sender_id' => auth()->user()->id,
                    'receiver_id' => $id,
                ]);

            $message = Message::create([
                'body' => $request->get('body'),
                'user_id' => auth()->user()->id,
                'room_id' => $room->id,
            ]);

            broadcast(new MessageEvent($message))->toOthers();
            return $this->success('تم الارسال بنجاح');
        }
    }//end of sendMessage
}
