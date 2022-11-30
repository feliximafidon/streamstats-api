<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\ModelBase;
use Illuminate\Contracts\Auth\Authenticatable as AuthAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use ModelBase;

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
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Key for Twitch cache
     * 
     * @var string
     */
    protected $twitchCacheKey = 'twitch.userStreams';

    /**
     * Compute and cache Twitch Stats
     * 
     * @return void
     */
    public function getTwitchStats()
    {
        User::refreshTwitchCache(Auth::user());

        return [
            ...Cache::get('aggregates.general'),
            ...Cache::get('aggregates.user.' . $this->twitch_id), //@TODO: Remove caching for user-specific, no longer makes sense to cache
        ];
    }

    /**
     * Get user stream data
     * 
     * @param string $twitchUserId
     * @param string $userAccessToken
     * @return \Illuminate\Support\Collection
     */
    public static function getUserStreams(string $twitchUserId, string $userAccessToken): Collection
    {
        self::_initializeTwitch($userAccessToken);

        $streams = collect([]);

        do {
            $cursor = null;
        
            if (isset($fetchedStreams)) {
                $cursor = $fetchedStreams->next();
            }
        
            $fetchedStreams = self::$twitch->getFollowedStreams(['first' => 100, 'user_id' => $twitchUserId], $cursor);

            $streams = $streams->merge($fetchedStreams->data());
        
        } while ($fetchedStreams->hasMoreResults());

        return $streams;
    }

    /**
     * Refresh Twitch Cache for user aggregates
     * 
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public static function refreshTwitchCache(AuthAuthenticatable $user)
    {
        $twitchAccessToken = Cache::get('user.accessToken.' . $user->twitch_id);

        $userStreams = self::getUserStreams($user->twitch_id, $twitchAccessToken);
        $userStreamsIds = $userStreams->pluck('id')->toArray();

        // Cache result
        Cache::put(User::getTwitchCacheKey() . '.' . $user->twitch_id, $userStreams); // @TODO: Check --- Do we really need to do this, since we are using this one, after which we store the aggregated values?

        // Compute and cache user-dependent aggregates
        $streams = Cache::get(Stream::getTwitchCacheKey(), Stream::data()->get());
        $streamsIds = $streams->pluck('id')->toArray();

        /**
         * User-specific aggregates
         * 
         * @NOTE: (#Assignment) The last two aggregates are computed from array values (implemented as \Illuminate\Support\Collection). The first is a database query.
         */ 
        //'top_streams_following' => $userStreams->intersect($streams), // Will fail; array is not flat, but an array of objects

        /**
         * Which of the top 1000 streams is the logged in user following
         */
        $top_streams_following = $streams->whereIn('id', array_intersect($userStreamsIds, $streamsIds))->values();

        // Unused
        $tags_intersect = Stream::data()->whereJsonContains(
            'tag_ids', 
            $userStreams->count() ? 
                $userStreams->pluck('tag_ids')->reduce(
                    function($carry, $item) { 
                        return array_merge($carry ?: [], $item); 
                    }) : 
                null
        )->get();
        
        /**
         * How many viewers does the lowest viewer count stream that the logged in user is following need to gain in order to make it into the top 1000
         * 
         * Note: Added one to the result because it has to push the last off the top 1000 to join the top 1000
         */ 
        $lowest_following_diff_top_1000 = -100; $userStreams->count() ? 
            ($streams->sortBy('viewer_count')->first()?->viewer_count - $userStreams->sortBy('viewer_count')->first()?->viewer_count + 1) : 
            null;
        
        /**
         * Which tags are shared between the user followed streams and the top 1000 streams
         */
        $shared_tags = array_intersect(
            array_values(array_unique($userStreams->pluck('tag_ids')->reduce(function($carry, $item) { return array_merge($carry ?: [], $item); }) ?: [])),
            array_values(array_unique($streams->pluck('tag_ids')->reduce(function($carry, $item) { return array_merge($carry ?: [], $item); }) ?: []))
        );

        $aggregates = [
            'top_streams_following' => $top_streams_following,
            'lowest_following_diff_top_1000' => $lowest_following_diff_top_1000, 
            'shared_tags' => $shared_tags,
        ];

        Cache::put('aggregates.user.' . $user->twitch_id, $aggregates);
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
}
