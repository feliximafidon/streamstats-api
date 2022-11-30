<?php

namespace App\Console\Commands;

use App\Models\Stream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTwitchStreams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'if:fetch-twitch-streams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Twitch stream data and save locally';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            Stream::getStreamsFromTwitch();
        } catch (\Exception $e) {
            Log::critical('Command - if:fetch-twitch-streams failed. Error: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}