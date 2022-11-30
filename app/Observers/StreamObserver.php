<?php

namespace App\Observers;

use App\Models\Stream;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StreamObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
    
    /**
     * Handle the Stream "created" event.
     *
     * @param  \App\Models\Stream  $stream
     * @return void
     */
    public function created(Stream $stream)
    {
        $this->_resetTwitchCache($stream);
    }

    /**
     * Handle the Stream "updated" event.
     *
     * @param  \App\Models\Stream  $stream
     * @return void
     */
    public function updated(Stream $stream)
    {
        $this->_resetTwitchCache($stream);
    }

    /**
     * Handle the Stream "deleted" event.
     *
     * @param  \App\Models\Stream  $stream
     * @return void
     */
    public function deleted(Stream $stream)
    {
        $this->_resetTwitchCache($stream);
    }

    /**
     * Handle the Stream "restored" event.
     *
     * @param  \App\Models\Stream  $stream
     * @return void
     */
    public function restored(Stream $stream)
    {
        $this->_resetTwitchCache($stream);
    }

    /**
     * Handle the Stream "force deleted" event.
     *
     * @param  \App\Models\Stream  $stream
     * @return void
     */
    public function forceDeleted(Stream $stream)
    {
        $this->_resetTwitchCache($stream);
    }

    /**
     * Forget the cache
     * 
     * @param  \App\Models\Stream  $stream
     * @return void 
     */
    private function _resetTwitchCache(Stream $stream)
    {
        Stream::refreshTwitchCache();
    }
}
