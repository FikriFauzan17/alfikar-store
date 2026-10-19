<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class ApplicationBackupService
{
    /**
     * @return array{id: string|null, status: 'completed'|'skipped', image_file_count: int, size_bytes: int, retention_warning: bool}
     */
    public function run(): array
    {
        $root = $this->preparePrivateRoot();
        $lock = @fopen($root.DIRECTORY_SEPARATOR.'.backup.lock', 'c+b');

        if (! is_resource($lock)) {
            throw new RuntimeException('Unable to open the private backup lock.');
        }

        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            $this->recordRun('skipped', null, 0);

            return [
                'id' => null,
                'status' => 'skipped',
                'image_file_count' => 0,
                'size_bytes' => 0,
                'retention_warning' => false,
            ];
        }

        $id = 'backup-'.now()->format('Ymd-His').'-'.Str::uuid();
        $stagingDirectory = $root.DIRECTORY_SEPARATOR.'.'.$id.'.partial';
        $backupDirectory = $root.DIRECTORY_SEPARATOR.$id;

        try {
            if (! @mkdir($stagingDirectory, 0700)) {
                throw new RuntimeException('Unable to create the private backup snapshot directory.');
            }

            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $databaseFilename = match ($driver) {
                'sqlite' => 'database.sqlite',
                'mysql', 'mariadb' => 'database.sql',
                default => throw new RuntimeException('The configured database driver is not supported for backups.'),
            };
            $databasePath = $stagingDirectory.DIRECTORY_SEPARATOR.$databaseFilename;

            if ($driver === 'sqlite') {
                $this->backupSqlite($connection->getPdo(), (string) $connection->getDatabaseName(), $databasePath);
            } else {
                $this->backupMysql($connection->getPdo(), $databasePath);
            }

            $imageDiskName = (string) config('backup.image_disk');
            [$imageFileCount, $imageBytes] = $this->backupImages($imageDiskName, $stagingDirectory.DIRECTORY_SEPARATOR.'images');
            $databaseBytes = @filesize($databasePath);

            if ($databaseBytes === false) {
                throw new RuntimeException('Unable to read the completed database backup size.');
            }

            $sizeBytes = $databaseBytes + $imageBytes;
            $manifest = [
                'created_at' => now()->toIso8601String(),
                'status' => 'completed',
                'database_driver' => $driver,
                'database_file' => $databaseFilename,
                'image_disk' => $imageDiskName,
                'image_file_count' => $imageFileCount,
                'database_bytes' => $databaseBytes,
                'image_bytes' => $imageBytes,
                'size_bytes' => $sizeBytes,
            ];
            $manifestPath = $stagingDirectory.DIRECTORY_SEPARATOR.'manifest.json';

            if (file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Unable to write the backup manifest.');
            }

            if (! @rename($stagingDirectory, $backupDirectory)) {
                throw new RuntimeException('Unable to finalize the backup snapshot.');
            }

            $this->recordRun('completed', $id, $sizeBytes);
            $retentionWarning = false;

            try {
                $this->pruneExpiredBackups($root);
            } catch (RuntimeException $exception) {
                $retentionWarning = true;
                Log::warning('Backup completed, but retention cleanup failed.', [
                    'status' => 'retention_failed',
                ]);
            }

            return [
                'id' => $id,
                'status' => 'completed',
                'image_file_count' => $imageFileCount,
                'size_bytes' => $sizeBytes,
                'retention_warning' => $retentionWarning,
            ];
        } catch (Throwable $exception) {
            if (is_dir($stagingDirectory) && ! File::deleteDirectory($stagingDirectory)) {
                Log::warning('Failed backup left a private incomplete snapshot for later cleanup.', [
                    'status' => 'cleanup_failed',
                ]);
            }

            $this->recordRun('failed', $id, 0);

            throw $exception;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function preparePrivateRoot(): string
    {
        $configuredPath = (string) config('backup.path');

        if (! $this->isAbsolutePath($configuredPath)) {
            throw new RuntimeException('BACKUP_PATH must be an absolute directory outside the application.');
        }

        $existingParent = dirname($configuredPath);

        while (! file_exists($existingParent)) {
            $parent = dirname($existingParent);

            if ($parent === $existingParent) {
                throw new RuntimeException('Unable to resolve the private backup directory parent.');
            }

            $existingParent = $parent;
        }

        $resolvedParent = realpath($existingParent);
        $applicationRoot = realpath(base_path());

        if ($resolvedParent === false || $applicationRoot === false || $this->isWithinDirectory($resolvedParent, $applicationRoot)) {
            throw new RuntimeException('The backup directory must resolve outside the application and public directories.');
        }

        if (! is_dir($configuredPath) && ! @mkdir($configuredPath, 0700, true) && ! is_dir($configuredPath)) {
            throw new RuntimeException('Unable to create the private backup directory.');
        }

        $root = realpath($configuredPath);

        if (
            $root === false
            || $applicationRoot === false
            || $this->isWithinDirectory($root, $applicationRoot)
            || $root === dirname($root)
        ) {
            throw new RuntimeException('The backup directory must resolve outside the application and public directories.');
        }

        $permissionMarker = $root.DIRECTORY_SEPARATOR.'.permissions-verified';

        if (! is_file($permissionMarker)) {
            $entries = scandir($root);

            if ($entries === false || count($entries) > 2) {
                throw new RuntimeException('Use an empty, dedicated backup directory on first configuration.');
            }
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->restrictWindowsPermissions($root);
        } elseif (! @chmod($root, 0700)) {
            throw new RuntimeException('Unable to restrict permissions on the backup directory.');
        }

        if (! is_file($permissionMarker) && file_put_contents($permissionMarker, 'private', LOCK_EX) === false) {
            throw new RuntimeException('Unable to save the backup permissions marker.');
        }

        return $root;
    }

    private function restrictWindowsPermissions(string $directory): void
    {
        $domain = trim((string) getenv('USERDOMAIN'));
        $username = trim((string) getenv('USERNAME'));
        $identity = $domain !== '' && $username !== '' ? $domain.'\\'.$username : $username;

        if ($identity === '') {
            $identityProcess = new Process(['whoami.exe']);
            $identityProcess->setTimeout(10);
            $identityProcess->run();
            $identity = trim($identityProcess->getOutput());
        }

        if ($identity === '' || str_contains($identity, "\n")) {
            throw new RuntimeException('Unable to determine the Windows account for backup directory permissions.');
        }

        $process = new Process(['icacls.exe', $directory, '/inheritance:r', '/grant:r', $identity.':(OI)(CI)F']);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Unable to restrict Windows backup directory permissions.');
        }
    }

    private function backupSqlite(PDO $connection, string $databasePath, string $destination): void
    {
        if ($databasePath === ':memory:' || str_starts_with($databasePath, 'file:')) {
            throw new RuntimeException('SQLite backups require a file-backed database connection.');
        }

        $quotedDestination = $connection->quote($destination);

        if ($quotedDestination === false) {
            throw new RuntimeException('Unable to quote the SQLite backup destination.');
        }

        $connection->exec('VACUUM INTO '.$quotedDestination);

        if (! is_file($destination)) {
            throw new RuntimeException('SQLite did not create the backup snapshot.');
        }
    }

    private function backupMysql(PDO $connection, string $destination): void
    {
        $stream = @fopen($destination, 'xb');

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to create the MySQL backup dump.');
        }

        try {
            $this->write($stream, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION WITH CONSISTENT SNAPSHOT;\n");
            $tables = $connection->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tables as $table) {
                $tableName = (string) array_values($table)[0];
                $quotedTable = $this->quoteMysqlIdentifier($tableName);
                $schema = $connection->query('SHOW CREATE TABLE '.$quotedTable)->fetch(PDO::FETCH_ASSOC);
                $createStatement = $schema['Create Table'] ?? null;

                if (! is_string($createStatement)) {
                    throw new RuntimeException('Unable to read a database table definition for backup.');
                }

                $this->write($stream, $createStatement.";\n");
            }

            foreach ($tables as $table) {
                $tableName = (string) array_values($table)[0];
                $quotedTable = $this->quoteMysqlIdentifier($tableName);
                $columns = $connection->query('SHOW COLUMNS FROM '.$quotedTable)->fetchAll(PDO::FETCH_ASSOC);
                $writableColumns = array_values(array_filter(
                    $columns,
                    fn (array $column): bool => ! str_contains(strtolower((string) ($column['Extra'] ?? '')), 'virtual generated')
                        && ! str_contains(strtolower((string) ($column['Extra'] ?? '')), 'stored generated'),
                ));
                $columnNames = array_map(fn (array $column): string => (string) $column['Field'], $writableColumns);

                if ($columnNames === []) {
                    continue;
                }

                $quotedColumns = implode(', ', array_map($this->quoteMysqlIdentifier(...), $columnNames));
                $statement = $connection->query('SELECT * FROM '.$quotedTable);

                while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $values = [];

                    foreach ($writableColumns as $column) {
                        $name = (string) $column['Field'];
                        $value = $row[$name] ?? null;
                        $type = strtolower((string) ($column['Type'] ?? ''));

                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_resource($value)) {
                            $value = stream_get_contents($value);
                            $values[] = "X'".bin2hex((string) $value)."'";
                        } elseif (str_contains($type, 'blob') || str_contains($type, 'binary')) {
                            $values[] = "X'".bin2hex((string) $value)."'";
                        } else {
                            $quotedValue = $connection->quote((string) $value, PDO::PARAM_STR);

                            if ($quotedValue === false) {
                                throw new RuntimeException('Unable to encode a database value for backup.');
                            }

                            $values[] = $quotedValue;
                        }
                    }

                    $this->write(
                        $stream,
                        'INSERT INTO '.$quotedTable.' ('.$quotedColumns.') VALUES ('.implode(', ', $values).");\n",
                    );
                }
            }

            $this->write($stream, "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n");

            if (! fflush($stream)) {
                throw new RuntimeException('Unable to flush the MySQL database backup.');
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return array{int, int}
     */
    private function backupImages(string $diskName, string $destination): array
    {
        if (! @mkdir($destination, 0700, true) && ! is_dir($destination)) {
            throw new RuntimeException('Unable to create the private image backup directory.');
        }

        $disk = Storage::disk($diskName);
        $files = $disk->allFiles();
        sort($files, SORT_STRING);
        $fileCount = 0;
        $totalBytes = 0;

        foreach ($files as $path) {
            $relativePath = str_replace('\\', '/', (string) $path);
            $segments = explode('/', $relativePath);

            if ($relativePath === '' || str_starts_with($relativePath, '/') || in_array('..', $segments, true) || in_array('.', $segments, true)) {
                throw new RuntimeException('The configured image disk returned an unsafe file path.');
            }

            $targetPath = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetDirectory = dirname($targetPath);

            if (! is_dir($targetDirectory) && ! @mkdir($targetDirectory, 0700, true) && ! is_dir($targetDirectory)) {
                throw new RuntimeException('Unable to create a private image backup subdirectory.');
            }

            $source = $disk->readStream($path);
            $target = @fopen($targetPath, 'xb');

            if (! is_resource($source) || ! is_resource($target)) {
                if (is_resource($source)) {
                    fclose($source);
                }

                if (is_resource($target)) {
                    fclose($target);
                }

                throw new RuntimeException('Unable to open an image for backup.');
            }

            try {
                $copiedBytes = stream_copy_to_stream($source, $target);

                if ($copiedBytes === false || ! fflush($target)) {
                    throw new RuntimeException('Unable to copy an image into the private backup.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }

            $fileCount++;
            $totalBytes += $copiedBytes;
        }

        return [$fileCount, $totalBytes];
    }

    private function pruneExpiredBackups(string $root): void
    {
        $cutoff = now()->subDays(max(1, (int) config('backup.retention_days', 30)));

        foreach (File::directories($root) as $directory) {
            $name = basename($directory);

            if (preg_match('/\A\.backup-\d{8}-\d{6}-[0-9a-f-]{36}\.partial\z/i', $name) === 1) {
                if (filemtime($directory) !== false && filemtime($directory) < now()->subDay()->timestamp) {
                    if (! File::deleteDirectory($directory)) {
                        throw new RuntimeException('Unable to remove an expired incomplete backup.');
                    }
                }

                continue;
            }

            if (! str_starts_with($name, 'backup-')) {
                continue;
            }

            $manifestPath = $directory.DIRECTORY_SEPARATOR.'manifest.json';

            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($manifest) || ($manifest['status'] ?? null) !== 'completed' || ! is_string($manifest['created_at'] ?? null)) {
                continue;
            }

            try {
                $createdAt = Carbon::parse($manifest['created_at']);
            } catch (\Exception) {
                continue;
            }

            if ($createdAt->lessThan($cutoff) && ! File::deleteDirectory($directory)) {
                throw new RuntimeException('Unable to remove an expired backup snapshot.');
            }
        }
    }

    private function recordRun(string $status, ?string $id, int $sizeBytes): void
    {
        $historyPath = (string) config('backup.path').DIRECTORY_SEPARATOR.'backup-history.jsonl';
        $stream = @fopen($historyPath, 'ab');

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to record backup status in the private backup directory.');
        }

        try {
            if (! flock($stream, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the private backup history file.');
            }

            $record = json_encode([
                'created_at' => now()->toIso8601String(),
                'status' => $status,
                'id' => $id,
                'size_bytes' => $sizeBytes,
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

            $this->write($stream, $record);

            if (! fflush($stream)) {
                throw new RuntimeException('Unable to flush backup status metadata.');
            }

            flock($stream, LOCK_UN);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     */
    private function write(mixed $stream, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);

        while ($offset < $length) {
            $written = fwrite($stream, substr($contents, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write private backup data.');
            }

            $offset += $written;
        }
    }

    private function quoteMysqlIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) === 1
            || str_starts_with($path, '\\\\');
    }

    private function isWithinDirectory(string $path, string $directory): bool
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $directory = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory), DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $directory = strtolower($directory);
        }

        return $path === $directory || str_starts_with($path, $directory.DIRECTORY_SEPARATOR);
    }
}
