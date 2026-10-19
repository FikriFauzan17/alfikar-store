<?php

namespace App\Console\Commands;

use App\Services\ProductMetricRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('metrics:prune')]
#[Description('Delete daily product metrics beyond the configured retention period.')]
class PruneProductMetricsCommand extends Command
{
    public function handle(ProductMetricRecorder $metrics): int
    {
        $deleted = $metrics->pruneExpired();
        $this->info("Menghapus {$deleted} ringkasan harian yang kedaluwarsa.");

        return self::SUCCESS;
    }
}
