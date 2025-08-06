<?php

namespace Wsmallnews\FilamentNestedset\Commands;

use Illuminate\Console\Command;

class FilamentNestedsetCommand extends Command
{
    public $signature = 'filament-nestedset';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
