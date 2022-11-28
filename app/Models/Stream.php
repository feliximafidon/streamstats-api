<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use romanzipp\Twitch\Twitch;

class Stream extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'twitch_id',
        'username',
        'avatar',
    ];
    
    /**
     * The Twitch object
     * 
     * @var \romanzipp\Twitch\Twitch
     */
    protected $twitch = null;

    public function getStreamsFromTwitch() 
    {
        $this->_initializeTwitch();

        /**
         * Issue: 
         * 1. Because viewers come and go during a stream, it’s possible to find duplicate or missing streams in the list as you page through the results
         * 2. We are trying to get 1000 records, but we can only get 100 max at a time
         * 
         * Impact:
         * If we have to make 10 calls to get 100 records each, depending on the time lapse in between, there's a chance that:
         * 1. Duplicates will exist in between calls
         * 2. We might miss the "latest stream with the highest viewers" when we're calling for pages after page 1
         * 
         * Fix:
         * 1. Until we can get an API to get all 1000 at a time, we may have to just ensure that the time lapse between each call is near 0
         * 2. We could leverage Swoole to make the 10 calls concurrently (near instant)
         */

        $streams = collect([]);

        $streamsCursor = $this->twitch->getStreams([
            'type' => 'live',
            'first' => 100,
        ]);

        // return $streamsCursor->data();

        for ($i = 0; $i < 10; $i++) {
            $streams = $streams->merge($streamsCursor->data());

            if (! $streamsCursor->hasMoreResults()) {
                break;
            }

            $streamsCursor->next();
        }

        return $streams;
    }
    
    /**
     * Initialise the Twitch object
     * 
     * @return void
     */
    private function _initializeTwitch()
    {
        $this->twitch = new Twitch;

        $this->twitch->setClientId(config('twitch-api.client_id'));
    }
}
