<?php

namespace Nosok\XeroReport\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class ReportCreated extends Notification
{
    use Queueable;

    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function via($notifiable)
    {
        return ['slack'];
    }

    public function toSlack($notifiable)
    {
        //TODO:: validate config params, catch exceptions
        $slack = (object) config('xeroreport.notifications.slack');

        if (!is_null($notifiable->getSlackChannel())) {
            $slack->channelName = $notifiable->getSlackChannel();
        } else {
            $slack->channelName = $slack->channel['default'];
        }

        return (new SlackMessage)
            ->success()
            ->from($slack->username, $slack->icon)
            ->to($slack->channelName)
            ->content('Weekly Xero Report')
            ->attachment(function ($attachment) {
                $attachment->content($this->content);
            });
    }
}
