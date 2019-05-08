<?php

namespace Nosok\XeroReport\Reports;

use Illuminate\Notifications\Notifiable;

class BaseReport
{
    use Notifiable;

    private $slackChannel = null;

    public function routeNotificationForSlack($notification)
    {
        // TODO:: report error if param is not initialized
        return config('xeroreport.notifications.slack.webhook_url');
    }

    public function setSlackChannel($value = null)
    {
        $this->slackChannel = $value;
        return $this;
    }

    public function getSlackChannel()
    {
        return $this->slackChannel;
    }
}
