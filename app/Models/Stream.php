<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        'id',
        'user_id',
        'user_name',
        'game_id',
        'game_name',
        'type',
        'title',
        'viewer_count',
        'thumbnail_url',
    ];
    
    /**
     * The Twitch object
     * 
     * @var \romanzipp\Twitch\Twitch
     */
    protected static $twitch = null;

    public static function getStreamsFromTwitch(): array
    {
        self::_initializeTwitch();

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
        $fetchedStreams = null;

        for ($i = 0; $i < 10; $i++) {
            $parameters = [
                'type' => 'live',
                'first' => 100,
            ];

            if ($fetchedStreams) {
                // We have fetched records before, so we set pagination
                $cursor = $fetchedStreams->getPaginator()?->cursor();

                if ($cursor) {
                    $parameters['after'] = $cursor;
                } else {
                    /**
                     * The Pagination object is empty if there are no more pages to return in the direction you’re paging.
                     * Ref: https://dev.twitch.tv/docs/api/guide#pagination
                     */
                    break;
                }
            }

            $fetchedStreams = self::$twitch->getStreams($parameters);
            
            $streams = $streams->merge($fetchedStreams->data());
        }

        return DB::transaction(function () use ($streams) {
            $self = new static();

            self::query()->update(['is_archived' => true]);
            $data = $streams->unique('id')->map(function ($stream) use ($self) {
                return collect($stream)->only($self->getFillable());
            })->toArray();

            shuffle($data);
            
            Stream::insert($data);

            return $data;
        });
    }
    
    /**
     * Initialise the Twitch object
     * 
     * @return void
     */
    private static function _initializeTwitch()
    {
        self::$twitch = new Twitch;

        self::$twitch->setClientId(config('twitch-api.client_id'));
    }
}
