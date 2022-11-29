<?php

namespace App\Models;

use App\Traits\ModelBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use romanzipp\Twitch\Twitch;

class StreamTag extends Model
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
        'name',
        'description',
    ];

    /**
     * Key for Twitch cache
     * 
     * @var string
     */
    protected $twitchCacheKey = 'twitch.tags';

    /**
     * Fetch information for tags, updating any changes
     * 
     * @return void
     */
    public static function getTagsInformation()
    {
        self::_initializeTwitch();

        $tags = [];

        do {
            $cursor = null;
        
            if (isset($fetchedTags)) {
                $cursor = $fetchedTags->next();
            }
        
            $fetchedTags = self::$twitch->getAllStreamTags(['first' => 100], $cursor);
        
            foreach ($fetchedTags->data() as $tag) {
                // @TODO: Application-wide multiple language support. For now, we have taken only English
                $tags[] = [
                    'id' => $tag->tag_id,
                    'name' => $tag->localization_names->{'en-us'},
                    'description' => $tag->localization_descriptions->{'en-us'},
                ];
            }
        
        } while ($fetchedTags->hasMoreResults());

        StreamTag::upsert($tags, ['id']);
    }

    /**
     * Check if tags exist. If not, fetch and save
     * 
     * @param array $tags
     * @return void
     */
    public static function getUnavailableTagsInformation($tagIds)
    {
        self::_initializeTwitch();

        $existingTags = StreamTag::whereIn('id', $tagIds)->select(['id'])->get()->pluck('id')->toArray();

        /**
         * Find the difference between both arrays
         * array_diff is mischevious with the order of the arguments
         * Using array_merge for both directions fixes the issue
         */
        $newTagIds = array_merge(array_diff($existingTags, $tagIds), array_diff($tagIds, $existingTags)); 

        if (! $newTagIds) {
            return;
        }

        $tags = [];

        foreach (array_chunk($newTagIds, 100) as $newTagsHundred) {
            do {
                $cursor = null;
            
                if (isset($fetchedTags)) {
                    $cursor = $fetchedTags->next();
                }
            
                $fetchedTags = self::$twitch->getAllStreamTags(['first' => 100, 'tag_id' => $newTagsHundred], $cursor);
            
                foreach ($fetchedTags->data() as $tag) {
                    // @TODO: Application-wide multiple language support. For now, we have taken only English
                    $tags[] = [
                        'id' => $tag->tag_id,
                        'name' => $tag->localization_names->{'en-us'},
                        'description' => $tag->localization_descriptions->{'en-us'},
                    ];
                }
            
            } while ($fetchedTags->hasMoreResults());
        }

        self::upsert($tags, ['id']);

        // Refresh Twitch cache
        self::refreshTwitchCache();
    }    

    /**
     * Scope query to define default fetch configuration
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeData($query)
    {
        return $query->select(['id', 'name', 'description']);
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
     * Refresh Twitch Cache
     * 
     * @return void
     */
    public static function refreshTwitchCache()
    {
        Cache::put(self::getTwitchCacheKey(), static::data()->get()->keyBy('id'));
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string', // ->pluck(id) returns int without this line, which truncates the non-integers in the string
    ];
}
