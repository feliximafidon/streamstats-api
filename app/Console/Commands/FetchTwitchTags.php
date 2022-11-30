<?php

namespace App\Console\Commands;

use App\Models\StreamTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTwitchTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'if:fetch-twitch-tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Twitch tags and save locally';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            StreamTag::getTagsInformation();
        } catch (\Exception $e) {
            Log::critical('Command - if:fetch-twitch-tags failed. Error: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}