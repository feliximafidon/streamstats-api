<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

class FetchTwitchData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'if:fetch-twitch-data';

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
        

        return Command::SUCCESS;
    }
}