<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : Number of newest backups to keep}';

    protected $description = 'Create a compressed backup of the application MySQL database';

    public function handle(): int
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->error("This command currently supports only the MySQL connection.");

            return self::FAILURE;
        }

        $config = config("database.connections.{$connection}");

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        if ($database === '' || $username === '') {
            $this->error('Database name or username is missing from the application configuration.');

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups/database');

        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeDatabase = preg_replace('/[^A-Za-z0-9_.-]/', '_', $database);

        $sqlPath = "{$backupDir}/{$safeDatabase}_{$timestamp}.sql";
        $gzipPath = $sqlPath . '.gz';

        $defaultsFile = tempnam(sys_get_temp_dir(), 'mysql-backup-');

        if ($defaultsFile === false) {
            $this->error('Could not create the temporary MySQL credentials file.');

            return self::FAILURE;
        }

        try {
            $defaults = "[client]\n"
                . 'user="' . $this->escapeCnfValue($username) . "\"\n"
                . 'password="' . $this->escapeCnfValue($password) . "\"\n"
                . 'host="' . $this->escapeCnfValue($host) . "\"\n"
                . 'port="' . $this->escapeCnfValue($port) . "\"\n";

            file_put_contents($defaultsFile, $defaults);
            @chmod($defaultsFile, 0600);

            $this->info("Creating database backup: {$database}");

            $process = new Process([
                '/usr/bin/mysqldump',
                "--defaults-extra-file={$defaultsFile}",
                '--single-transaction',
                '--quick',
                '--lock-tables=false',
                '--routines',
                '--triggers',
                '--events',
                '--default-character-set=utf8mb4',
                $database,
            ]);

            $process->setTimeout(600);

            $sqlHandle = fopen($sqlPath, 'wb');

            if ($sqlHandle === false) {
                throw new \RuntimeException("Could not create SQL backup file.");
            }

            $stderr = '';

            try {
                $process->run(function (string $type, string $buffer) use ($sqlHandle, &$stderr): void {
                    if ($type === Process::OUT) {
                        fwrite($sqlHandle, $buffer);
                    } else {
                        $stderr .= $buffer;
                    }
                });
            } finally {
                fclose($sqlHandle);
            }

            if (! $process->isSuccessful()) {
                @unlink($sqlPath);

                $message = trim($stderr) ?: 'mysqldump failed.';
                throw new \RuntimeException($message);
            }

            $this->compressFile($sqlPath, $gzipPath);

            @unlink($sqlPath);

            $keep = max(1, (int) $this->option('keep'));
            $this->deleteOldBackups($backupDir, $keep);

            $size = File::size($gzipPath);

            $this->newLine();
            $this->info('Backup completed successfully.');
            $this->line("File: {$gzipPath}");
            $this->line('Size: ' . $this->humanFileSize($size));
            $this->line("Keeping newest {$keep} backups.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            @unlink($sqlPath);
            @unlink($gzipPath);

            $this->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($defaultsFile);
        }
    }

    private function compressFile(string $source, string $destination): void
    {
        $input = fopen($source, 'rb');

        if ($input === false) {
            throw new \RuntimeException('Could not open SQL backup for compression.');
        }

        $output = gzopen($destination, 'wb9');

        if ($output === false) {
            fclose($input);

            throw new \RuntimeException('Could not create compressed backup file.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);

                if ($chunk === false) {
                    throw new \RuntimeException('Could not read SQL backup during compression.');
                }

                if ($chunk !== '') {
                    gzwrite($output, $chunk);
                }
            }
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    private function deleteOldBackups(string $backupDir, int $keep): void
    {
        $files = collect(File::files($backupDir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.sql.gz'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $files
            ->slice($keep)
            ->each(fn ($file) => @unlink($file->getPathname()));
    }

    private function escapeCnfValue(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r"],
            ['\\\\', '\\"', '\n', '\r'],
            $value
        );
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) {
            return number_format($bytes / (1024 ** 3), 2) . ' GB';
        }

        if ($bytes >= 1024 ** 2) {
            return number_format($bytes / (1024 ** 2), 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
