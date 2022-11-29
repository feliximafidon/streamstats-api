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
        $this->_resetTwitchCache();
    }

    /**
     * Handle the StreamTag "updated" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function updated(StreamTag $streamTag)
    {
        $this->_resetTwitchCache();
    }

    /**
     * Handle the StreamTag "deleted" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function deleted(StreamTag $streamTag)
    {
        $this->_resetTwitchCache();
    }

    /**
     * Handle the StreamTag "restored" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function restored(StreamTag $streamTag)
    {
        $this->_resetTwitchCache();
    }

    /**
     * Handle the StreamTag "force deleted" event.
     *
     * @param  \App\Models\StreamTag  $streamTag
     * @return void
     */
    public function forceDeleted(StreamTag $streamTag)
    {
        $this->_resetTwitchCache();
    }

    /**
     * Forget the cache
     * 
     * @return void
     */
    private function _resetTwitchCache()
    {
        Cache::put(StreamTag::select(['id', 'name', 'description'])->limit(2)->get()->keyBy('id'));
    }
}
