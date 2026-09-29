<?php

namespace App\Console\Commands;

use App\Services\ArchiveServedOrders as ArchiveServedOrdersService;
use Illuminate\Console\Command;

class ArchiveServedOrders extends Command
{
    protected $signature = 'orders:archive-served';

    protected $description = 'Archive orders served before the current day';

    public function handle(): int
    {
        $archived = app(ArchiveServedOrdersService::class)->run();

        $this->info("Archived {$archived} served order(s).");

        return self::SUCCESS;
    }
}