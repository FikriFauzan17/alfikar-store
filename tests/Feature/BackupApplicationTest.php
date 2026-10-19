<?php

use App\Services\ApplicationBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('backs up and restores database data and public images to isolated destinations', function () {
    $testDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alfikar-backup-test-'.Str::uuid();
    $backupDirectory = $testDirectory.DIRECTORY_SEPARATOR.'private-backups';
    $sourceDatabase = $testDirectory.DIRECTORY_SEPARATOR.'source.sqlite';
    $restoredDatabase = $testDirectory.DIRECTORY_SEPARATOR.'restored.sqlite';
    $imagePath = 'products/cover.webp';

    File::ensureDirectoryExists($testDirectory);
    file_put_contents($sourceDatabase, '');

    $image = imagecreatetruecolor(2, 2);
    ob_start();
    imagefilledrectangle($image, 0, 0, 1, 1, imagecolorallocate($image, 4, 120, 87));
    expect(imagewebp($image))->toBeTrue();
    $imageContents = ob_get_clean();
    imagedestroy($image);

    expect(getimagesizefromstring($imageContents)['mime'])->toBe('image/webp');

    $sqliteConfiguration = config('database.connections.sqlite');
    $sqliteConfiguration['database'] = $sourceDatabase;

    config([
        'database.default' => 'backup_source',
        'database.connections.backup_source' => $sqliteConfiguration,
        'backup.path' => $backupDirectory,
        'backup.image_disk' => 'public',
    ]);

    DB::purge('backup_source');

    try {
        DB::connection('backup_source')->statement('CREATE TABLE backup_records (id INTEGER PRIMARY KEY, payload TEXT NOT NULL)');
        DB::connection('backup_source')->statement('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY, created_at DATETIME NOT NULL)');
        DB::connection('backup_source')->table('backup_records')->insert([
            'id' => 1,
            'payload' => 'verified-restored-record',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put($imagePath, $imageContents);

        $this->artisan('backup:run')->assertSuccessful();

        $snapshots = array_values(array_filter(
            File::directories($backupDirectory),
            fn (string $directory): bool => str_starts_with(basename($directory), 'backup-'),
        ));

        expect($snapshots)->toHaveCount(1);

        $snapshotDirectory = $snapshots[0];
        $manifest = json_decode(
            (string) file_get_contents($snapshotDirectory.DIRECTORY_SEPARATOR.'manifest.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($manifest['status'])->toBe('completed')
            ->and($manifest['database_driver'])->toBe('sqlite')
            ->and($manifest['image_file_count'])->toBe(1)
            ->and($manifest['size_bytes'])->toBeGreaterThan(0);

        expect(File::copy(
            $snapshotDirectory.DIRECTORY_SEPARATOR.'database.sqlite',
            $restoredDatabase,
        ))->toBeTrue();

        $restoreConfiguration = $sqliteConfiguration;
        $restoreConfiguration['database'] = $restoredDatabase;
        config(['database.connections.backup_restore' => $restoreConfiguration]);
        DB::purge('backup_restore');

        expect(DB::connection('backup_restore')->table('backup_records')->value('payload'))
            ->toBe('verified-restored-record');

        $restoredImages = $testDirectory.DIRECTORY_SEPARATOR.'restored-images';
        expect(File::copyDirectory(
            $snapshotDirectory.DIRECTORY_SEPARATOR.'images',
            $restoredImages,
        ))->toBeTrue();

        expect(file_get_contents($restoredImages.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.'cover.webp'))
            ->toBe($imageContents);

        $expiredBackup = $backupDirectory.DIRECTORY_SEPARATOR.'backup-expired';
        File::ensureDirectoryExists($expiredBackup);
        file_put_contents($expiredBackup.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
            'created_at' => now()->subDays(60)->toIso8601String(),
            'status' => 'completed',
        ], JSON_THROW_ON_ERROR));

        $this->artisan('backup:run')->assertSuccessful();

        expect(is_dir($expiredBackup))->toBeFalse();

        $backupLock = fopen($backupDirectory.DIRECTORY_SEPARATOR.'.backup.lock', 'c+b');
        expect(is_resource($backupLock))->toBeTrue()
            ->and(flock($backupLock, LOCK_EX | LOCK_NB))->toBeTrue();

        try {
            $this->artisan('backup:run')
                ->expectsOutputToContain('Backup dilewati')
                ->assertSuccessful();
        } finally {
            flock($backupLock, LOCK_UN);
            fclose($backupLock);
        }

        $history = array_map(
            fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
            file($backupDirectory.DIRECTORY_SEPARATOR.'backup-history.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
        );

        expect($history)->toHaveCount(3)
            ->and(array_column($history, 'status'))->toBe(['completed', 'completed', 'skipped'])
            ->and($history[0]['status'])->toBe('completed')
            ->and($history[0]['size_bytes'])->toBe($manifest['size_bytes']);
    } finally {
        DB::purge('backup_source');
        DB::purge('backup_restore');
        config(['database.default' => 'sqlite']);
        File::deleteDirectory($testDirectory);
    }
});

it('rejects backup paths inside the application tree', function () {
    config(['backup.path' => storage_path('app/private/backup-test')]);

    expect(fn () => app(ApplicationBackupService::class)->run())
        ->toThrow(RuntimeException::class, 'The backup directory must resolve outside the application');
});
