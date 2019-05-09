<?php

namespace Nosok\XeroReport\Reports;

use Validator;
use Illuminate\Notifications\Notifiable;

class BaseReport
{
    use Notifiable;

    private $slackChannel = null;
    private $errors = [];

    public function validateConfig()
    {
        $validator = Validator::make(config('xeroreport'), [
            'xero.oauth.consumer_key'         => 'required|min:30',
            'xero.oauth.consumer_secret'      => 'required|min:30',
            'xero.oauth.rsa_private_key'      => 'required',
            'xero.oauth.rsa_public_key'       => 'required',
            'notifications.slack.webhook_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->addToErrors("config error :: {$error}");
            }
        }
    }

    public function routeNotificationForSlack()
    {
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

    public function addToErrors($value = null)
    {
        $this->errors[] = $value;
        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function kFormat($number = null)
    {
        if ($number > 999 && $number <= 999999) {
            return round($number / 1000, 1) . 'k';
        }
        if ($number > 999999) {
            return number_format((float)$number , 1, '.', '')/1000000 . 'm';
        }
        return $number;
    }
}
