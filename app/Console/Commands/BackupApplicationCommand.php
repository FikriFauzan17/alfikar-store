<?php

namespace App\Console\Commands;

use App\Services\ApplicationBackupService;
use App\Services\AuditLogRetentionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('backup:run')]
#[Description('Create a private backup and prune audit logs older than 12 months.')]
class BackupApplicationCommand extends Command
{
    public function handle(
        ApplicationBackupService $backupService,
        AuditLogRetentionService $auditLogs,
    ): int {
        $result = $backupService->run();

        if ($result['status'] === 'skipped') {
            $this->warn('Backup dilewati karena proses backup lain sedang berjalan.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Backup selesai: %s (%d file, %d byte).',
            $result['id'],
            $result['image_file_count'],
            $result['size_bytes'],
        ));

        if ($result['retention_warning']) {
            $this->warn('Backup berhasil, tetapi pembersihan retensi gagal; periksa izin direktori backup.');
        }

        $deletedAuditLogs = $auditLogs->pruneExpired();
        $this->info("Menghapus {$deletedAuditLogs} log audit yang lebih lama dari 12 bulan.");

        return self::SUCCESS;
    }
}
