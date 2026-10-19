<?php

use App\Services\ApplicationBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('prunes audit logs older than 12 months after a completed backup', function () {
    $this->travelTo(now()->setDate(2026, 10, 19)->setTime(12, 0));

    $expiredLogId = DB::table('audit_logs')->insertGetId([
        'event' => 'admin.expired',
        'created_at' => now()->subMonths(12)->subSecond(),
    ]);
    $boundaryLogId = DB::table('audit_logs')->insertGetId([
        'event' => 'admin.boundary',
        'created_at' => now()->subMonths(12),
    ]);
    $recentLogId = DB::table('audit_logs')->insertGetId([
        'event' => 'admin.recent',
        'created_at' => now()->subMonths(11),
    ]);

    $this->mock(ApplicationBackupService::class, function (MockInterface $mock) use ($expiredLogId): void {
        $mock->shouldReceive('run')
            ->once()
            ->andReturnUsing(function () use ($expiredLogId): array {
                $this->assertDatabaseHas('audit_logs', ['id' => $expiredLogId]);

                return [
                    'id' => 'backup-test',
                    'status' => 'completed',
                    'image_file_count' => 0,
                    'size_bytes' => 100,
                    'retention_warning' => false,
                ];
            });
    });

    $this->artisan('backup:run')
        ->expectsOutputToContain('Menghapus 1 log audit')
        ->assertSuccessful();

    $this->assertDatabaseMissing('audit_logs', ['id' => $expiredLogId]);
    $this->assertDatabaseHas('audit_logs', ['id' => $boundaryLogId]);
    $this->assertDatabaseHas('audit_logs', ['id' => $recentLogId]);
});

it('keeps expired audit logs when the backup is skipped', function () {
    $expiredLogId = DB::table('audit_logs')->insertGetId([
        'event' => 'admin.expired',
        'created_at' => now()->subMonths(13),
    ]);

    $this->mock(ApplicationBackupService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('run')
            ->once()
            ->andReturn([
                'id' => null,
                'status' => 'skipped',
                'image_file_count' => 0,
                'size_bytes' => 0,
                'retention_warning' => false,
            ]);
    });

    $this->artisan('backup:run')
        ->expectsOutputToContain('Backup dilewati')
        ->assertSuccessful();

    $this->assertDatabaseHas('audit_logs', ['id' => $expiredLogId]);
});
