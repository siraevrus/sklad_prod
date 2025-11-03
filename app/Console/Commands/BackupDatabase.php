<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать резервную копию базы данных';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = Config::get('database.default');
        $config = Config::get("database.connections.{$connection}");

        if ($config['driver'] !== 'mysql') {
            $this->error('Поддерживается только MySQL база данных');

            return Command::FAILURE;
        }

        $backupDir = base_path('backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'] ?? 3306;

        $filename = sprintf(
            '%s_backup_%s.sql',
            $database,
            now()->format('Ymd_His')
        );

        $filepath = $backupDir.'/'.$filename;

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p'.escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $this->info('Создание резервной копии базы данных...');

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Ошибка при создании резервной копии базы данных');

            return Command::FAILURE;
        }

        if (! file_exists($filepath) || filesize($filepath) === 0) {
            $this->error('Файл резервной копии не был создан или пуст');

            return Command::FAILURE;
        }

        $fileSize = $this->formatBytes(filesize($filepath));
        $this->info("Резервная копия успешно создана: {$filename} ({$fileSize})");

        // Удаляем старые бэкапы (оставляем последние 30)
        $this->cleanOldBackups($backupDir);

        return Command::SUCCESS;
    }

    /**
     * Форматирует размер файла в читаемый формат
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Удаляет старые резервные копии, оставляя только последние N файлов
     */
    private function cleanOldBackups(string $backupDir, int $keepCount = 30): void
    {
        $files = glob($backupDir.'/*_backup_*.sql');

        if (count($files) <= $keepCount) {
            return;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $filesToDelete = array_slice($files, $keepCount);

        foreach ($filesToDelete as $file) {
            @unlink($file);
            $this->line('Удален старый бэкап: '.basename($file));
        }
    }
}
