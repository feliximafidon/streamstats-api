<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateWeb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'if:update-web';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update public assets with build from twitch-web. Works only in local environment where the web project also exists.';

    protected $folder = '../twitch-web/dist';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('app.env') === 'local' && is_dir($source = base_path('../twitch-web/build')) && is_file($source . '/index.html')) {
            try {
                $this->copyDirectory($source, $destination = base_path('public'));
                copy($destination . '/index.html', base_path('resources/views/welcome.blade.php'));
                unlink($destination . '/index.html');

                $this->newLine();
                $this->comment('Copy complete.');
            } catch (\Exception $e) {
                $this->error('Copy failed. Error: ' . $e->getMessage());

                return Command::FAILURE;
            }
        } else {
            $this->comment('Environment not valid for web update.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function copyDirectory($source, $destination): bool
    {
        $dir = opendir($source); 
    
        if (! is_dir($destination) && ! mkdir($destination, recursive: false)) {
            throw new \Exception('Could not create directory: ' . $destination);
        }
    
        foreach (scandir($source) as $file) { 
    
            if (( $file != '.' ) && ( $file != '..' )) { 
                if (is_dir($source . '/' . $file)) {
                    if (! $this->copyDirectory($source . '/' . $file, $destination . '/' . $file)) {
                        throw new \Exception("Copy of '{$source}/{$file}' to {$destination}/{$file} failed.");
                    }
                } else { 
                    copy($source . '/' . $file, $destination . '/' . $file); 
                } 
            } 
        } 
    
        closedir($dir);

        return true;
    }
}
