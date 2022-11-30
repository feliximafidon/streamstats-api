<?php

namespace App\Traits;

use romanzipp\Twitch\Twitch;

trait ModelBase
{
    /**
     * The Twitch object
     * 
     * @var \romanzipp\Twitch\Twitch
     */
    protected static $twitch = null;

    /**
     * Initialise the Twitch object
     * 
     * @param string $userAccessToken
     * @return void
     */
    private static function _initializeTwitch(string $userAccessToken = null)
    {
        self::$twitch = new Twitch;

        self::$twitch->setClientId(config('twitch-api.client_id'));

        if ($userAccessToken) {
            self::$twitch->setToken($userAccessToken);
        }
    }
}