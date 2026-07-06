<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupSqliteCommand extends Command
{
    protected $signature = 'sqlite:backup {--keep=14 : How many most-recent backups to retain}';

    protected $description = 'Snapshot the SQLite database via the online backup API and prune old snapshots';

    public function handle(): int
    {
        $source = database_path('database.sqlite');
        if (config('database.default') === 'sqlite') {
            $source = config('database.connections.sqlite.database', $source);
        }

        if (! is_file($source)) {
            $this->error("SQLite database not found at {$source}");

            return self::FAILURE;
        }

        $backupDir = storage_path('backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, recursive: true);
        }

        // Microsecond suffix guards against a same-second collision if the command
        // is ever run manually more than once in quick succession.
        $target = $backupDir.'/checkstu-'.now()->format('Y-m-d_His').'-'.substr(uniqid(), -6).'.sqlite';

        // SQLite's online backup API (via ".backup") is safe to run against a live
        // database (including under WAL, with concurrent readers/writers) — unlike
        // a plain file copy, it can't capture a half-written page.
        DB::connection('sqlite')->getPdo()->exec(
            "VACUUM INTO '".addslashes($target)."'",
        );

        $this->info("Backup written: {$target} (".$this->humanSize(filesize($target)).')');

        $this->prune($backupDir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function prune(string $backupDir, int $keep): void
    {
        $files = collect(glob($backupDir.'/checkstu-*.sqlite'))
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->values();

        foreach ($files->slice($keep) as $stale) {
            unlink($stale);
            $this->line('Pruned old backup: '.basename($stale));
        }
    }

    private function humanSize(int $bytes): string
    {
        $mb = $bytes / 1_048_576;

        return number_format($mb, 1).' MB';
    }
}
