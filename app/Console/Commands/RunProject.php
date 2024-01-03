<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use function Termwind\render;
use function Termwind\terminal;

class RunProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = ' Attempt to lock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (1){
            $lock = Cache::lock('example',4);
            $f = $lock->get();
            $this->render($f);
            if($f){
                sleep(1);
                $lock->release();
            }
        }
    }
    public function render($f)
    {
        $bg = $f ? 'bg-green-600':'bg-red-600';
        $divs = Str::repeat("<div class='w-full $bg'> &nbsp;</div>",terminal()->width());
        render("<div>$divs</div>");
    }

}
