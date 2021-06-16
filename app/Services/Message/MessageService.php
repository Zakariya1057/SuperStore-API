<?php

namespace App\Services\Message;

use App\Models\Message;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Database\Eloquent\Collection;

class MessageService {

    private $sanitize_service;

    private const TO_USER_ID = 2;

    function __construct(SanitizeService $sanitize_service)
    {
        $this->sanitize_service = $sanitize_service;
    }
    
    public function index(string $message_type, int $user_id): Collection {
        
        $message_type = $this->sanitize_service->sanitizeField($message_type);

        $messages = Message::where('type', $message_type)
        ->where(function ($query) use($user_id){
            $query->where('from_user_id', $user_id)->orWhere('to_user_id', $user_id);
        })
        ->orderBy('created_at', 'ASC')->get();

        // If no messages found, then say something like this
        if(count($messages) == 0){
            $question_types = [
                'feedback' => 'Hey, what do you think about this app?',
                'help' => 'Hey, what can I help you with?',
                'issue' => 'Hey, what can I help you with?',
                'feature' => 'Hey, what features would you like added to this app?'
            ];

            return new Collection([ $this->create($question_types[$message_type], $message_type, static::TO_USER_ID, $user_id, 'received', true) ]);
        } else {
            $message_ids = [];

            foreach($messages as $message){
                $message->direction = $message->from_user_id == $user_id ? 'sent' : 'received';
                $message_ids[] = $message->id;
            }

            Message::whereIn('id', $message_ids)->update(['message_read' => 1]);
        }

        return $messages;
    }

    public function create(string $text, string $type, int $from_user_id, ?int $to_user_id = null, $direction = 'sent', $message_read = false): Message {
        $message = new Message();

        $message->type = $type;

        $message->text = $this->sanitize_service->sanitizeField($text);

        $message->from_user_id = $from_user_id;
        $message->to_user_id = $to_user_id ?? static::TO_USER_ID;

        $message->message_read = $message_read;

        $message->save();

        $message->direction = $direction;

        return $message;
    }

    public function unread_messages(int $user_id): Collection {
        $messages = Message::where([ ['to_user_id', $user_id], ['message_read', 0] ])->get();
        foreach($messages as $message){
            $message->direction = 'received';
        }

        return $messages;
    }
}
?>