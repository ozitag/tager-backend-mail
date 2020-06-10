<?php

namespace OZiTAG\Tager\Backend\Mail\Utils;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use OZiTAG\Tager\Backend\Mail\Exceptions\TagerMailSenderException;

class TagerMailSender
{
    public function send($to, $subject, $body, $eventData = [])
    {
        try {
            Mail::send([], ['eventData' => $eventData], function (Message $message) use ($to, $subject, $body) {
                $message->setBody($body, 'text/html', 'UTF-8');
                $message->setTo($to);
                $message->setSubject($subject);
            });
        } catch (\Exception $exception) {
            throw new TagerMailSenderException($exception->getMessage());
        }
    }
}