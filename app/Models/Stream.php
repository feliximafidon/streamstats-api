<?php

namespace App\Models;

use App\Traits\ModelBase;
use DateTime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use romanzipp\Twitch\Twitch;

class Stream extends Model
{
    use HasFactory;
    use ModelBase;

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
        'tag_ids',
        // 'tag_ids->id',
        // 'tag_ids->name',
        // 'tag_ids->description',
        'started_at',
    ];

    /**
     * Key for Twitch cache
     * 
     * @var string
     */
    protected $twitchCacheKey = 'twitch.streams';
    
    /**
     * Fetch top 1000 live streams from Twitch
     * 
     * @return void
     */
    public static function getStreamsFromTwitch()
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

        DB::transaction(function () use ($streams) {
            $tagIds = [];
            $self = new static();

            self::query()->update(['is_archived' => true]);
            $data = $streams->unique('id')->map(function ($stream) use ($self, &$tagIds) {
                collect($stream->tag_ids)->each(function ($tagId) use (&$tagIds) {
                    $tagIds[$tagId] = [ // Using the key is a hack to keep the array unique
                        'id' => $tagId,
                    ];
                });

                // Date RFC3339 to mysql-supported datetime; uses server timezone
                $stream->started_at = (new DateTime($stream->started_at))->format('Y-m-d H:i:s');

                // Json column cast fails for bulk inserts. Encode manually.
                $stream->tag_ids = json_encode($stream->tag_ids);

                return collect($stream)->only($self->getFillable());
            })->shuffle();
            
            self::insert($data->toArray());
            
            // Trigger cache refresh
            self::refreshTwitchCache();

            // Update stream tag information, for missing tagIds
            StreamTag::getUnavailableTagsInformation(array_keys($tagIds));
        });
    }

    /**
     * Get Twitch cache key
     * 
     * @return string
     */
    public static function getTwitchCacheKey(): string
    {
        return (new static)->twitchCacheKey;
    }

    /**
     * Scope query to define default fetch configuration
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeData($query)
    {
        return $query->select([
            'id', 
            'user_id',
            'user_name',
            'game_id',
            'game_name',
            // 'type', // Redundant
            'title',
            'viewer_count',
            'thumbnail_url',
            'tag_ids',
        ])->where('is_archived', false);
    }

    /**
     * Scope query 
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGamesTotalStreams($query)
    {
        return $query->groupBy('game_id')->selectRaw('COUNT(id) AS count, game_id')->orderByDesc('count');
    }

    /**
     * Scope query 
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGamesViewerCount($query)
    {
        return $query->groupBy('game_id')->selectRaw('SUM(viewer_count) AS count, game_id')->orderByDesc('count');
    }

    /**
     * Refresh Twitch Cache
     * 
     * @return void
     */
    public static function refreshTwitchCache()
    {
        Cache::put(self::getTwitchCacheKey(), static::data()->get());

        // Compute and cache general aggregates

        /**
         * General aggregates
         * @NOTE: (#Performance) This should not happen every time. It's the wrong place to have these calls. Should not be called by every user
         * @TODO: Move to the end of Stream::getStreamsFromTwitch(), and cache. Cache should live forever, but refreshed whenever data is fetched from Twitch
         * 
         * @NOTE: (#Assignment) Interestingly, all the aggregates here are database queries
         */
        $aggregates = [
            'games_total_streams' => static::gamesTotalStreams()->pluck('count', 'game_id'),
            'games_viewer_count' => static::gamesViewerCount()->pluck('count', 'game_id'),
            'median_viewer_count' => (int) DB::select('SELECT AVG(dd.viewer_count) as median_viewer_count FROM ( SELECT d.viewer_count, @rownum:=@rownum+1 as `row_number`, @total_rows:=@rownum FROM streams d, (SELECT @rownum:=0) r WHERE d.viewer_count is NOT NULL AND d.is_archived = 0 ORDER BY d.viewer_count ) as dd WHERE dd.row_number IN ( FLOOR((@total_rows+1)/2), FLOOR((@total_rows+2)/2) )')[0]?->median_viewer_count,
            'top_hundred' => static::data()->orderByDesc('viewer_count')->limit(100)->get(),
            'streams_by_time' => DB::select("SELECT count( id ) AS count, DATE_FORMAT( started_at, '%Y-%m-%d %H' ) AS datehour, 'datetime' AS type FROM streams GROUP BY datehour"),
            'streams_by_time_only' => DB::select("SELECT count( id ) AS count, DATE_FORMAT( started_at, '%H' ) AS datehour, 'time' AS type FROM streams GROUP BY datehour"),
        ];

        Cache::put('aggregates.general', $aggregates);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tag_ids' => 'array',
        'started_at' => 'datetime',
    ];
}
