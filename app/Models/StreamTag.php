<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use romanzipp\Twitch\Twitch;

class StreamTag extends Model
{
    use HasFactory;

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
     * The Twitch object
     * 
     * @var \romanzipp\Twitch\Twitch
     */
    protected static $twitch = null;

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

        StreamTag::upsert($tags, ['id']);
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string', // ->pluck(id) returns int without this line, which truncates the non-integers in the string
    ];
}
