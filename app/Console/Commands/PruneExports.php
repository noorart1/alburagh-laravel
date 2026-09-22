<?php

namespace App\Console\Commands;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Console\Command;

class PruneExports extends Command
{
    protected $signature = 'exports:prune {--days=7 : Delete Filament exports (Excel/CSV files) older than this many days}';

    protected $description = 'Delete old Filament export files (e.g. BookCatalog Excel exports) and their DB records';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $exports = Export::where('created_at', '<', now()->subDays($days))->get();

        foreach ($exports as $export) {
            $export->deleteFileDirectory();
            $export->delete();
        }

        $this->info("Pruned {$exports->count()} export(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
