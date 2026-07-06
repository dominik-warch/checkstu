<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

/**
 * No RefreshDatabase here — this command operates on the sqlite connection's
 * real file, so the test points that connection at its own temp file rather
 * than the shared :memory: test database (which VACUUM INTO can't target).
 * RefreshDatabase's per-test transaction wrapping around the default connection
 * conflicts with swapping the connection mid-test, so it's left out deliberately.
 */
class BackupSqliteCommandTest extends TestCase
{
    private string $tempDb;

    protected function setUp(): void
    {
        parent::setUp();

        $rawTemp = tempnam(sys_get_temp_dir(), 'checkstu_test_');
        $this->tempDb = $rawTemp.'.sqlite';
        unlink($rawTemp); // tempnam() creates the unsuffixed path; let the migrator create the real one fresh
        config(['database.connections.sqlite.database' => $this->tempDb]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        @unlink($this->tempDb);
        @unlink($this->tempDb.'-wal');
        @unlink($this->tempDb.'-shm');

        foreach (glob(storage_path('backups/checkstu-*.sqlite')) as $stray) {
            @unlink($stray);
        }

        parent::tearDown();
    }

    public function test_backup_creates_a_valid_restorable_snapshot_and_prunes_old_ones(): void
    {
        User::factory()->admin()->create(['username' => 'backuptest']);

        $this->assertSame(0, Artisan::call('sqlite:backup'));

        $files = glob(storage_path('backups/checkstu-*.sqlite'));
        $this->assertCount(1, $files, 'Expected exactly one backup file to exist');

        $backupPdo = new PDO('sqlite:'.$files[0]);
        $username = $backupPdo->query("SELECT username FROM users WHERE username = 'backuptest'")->fetchColumn();
        $this->assertSame('backuptest', $username);

        // Two more backups, then prune down to the 2 most recent.
        Artisan::call('sqlite:backup');
        Artisan::call('sqlite:backup', ['--keep' => 2]);

        $this->assertCount(2, glob(storage_path('backups/checkstu-*.sqlite')));
    }
}
