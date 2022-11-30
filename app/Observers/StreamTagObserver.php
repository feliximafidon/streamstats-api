<?php

namespace App\Observers;

use App\Models\StreamTag;
use Illuminate\Support\Facades\Cache;

class StreamTagObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the StreamTag "created" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function created(StreamTag $streamTag)
    {
        $this->_resetTwitchCache($streamTag);
    }

    /**
     * Handle the StreamTag "updated" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function updated(StreamTag $streamTag)
    {
        $this->_resetTwitchCache($streamTag);
    }

    /**
     * Handle the StreamTag "deleted" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function deleted(StreamTag $streamTag)
    {
        $this->_resetTwitchCache($streamTag);
    }

    /**
     * Handle the StreamTag "restored" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function restored(StreamTag $streamTag)
    {
        $this->_resetTwitchCache($streamTag);
    }

    /**
     * Handle the StreamTag "force deleted" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function forceDeleted(StreamTag $streamTag)
    {
        $this->_resetTwitchCache($streamTag);
    }

    /**
     * Forget the cache
     * 
     * @param  \App\Models\StreamTag  $streamTag
     * @return void 
     */
    private function _resetTwitchCache(StreamTag $streamTag)
    {
        StreamTag::refreshTwitchCache();
    }
}
