<?php

namespace App\Services;

use App\Models\Backup;

class BackupService
{
    public function create(): Backup
    {
        $db = config('database.connections.mysql');
        $filename = 'backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = 'backups/' . $filename;
        $outPath = storage_path('app/' . $path);

        $cmd = sprintf(
            '"C:\\xampp\\mysql\\bin\\mysqldump" --user=%s --host=%s --port=%s %s',
            $db['username'],
            $db['host'],
            $db['port'],
            $db['database']
        );

        $backup = Backup::create([
            'filename' => $filename,
            'path' => $path,
            'file_size' => 0,
            'status' => 'pending',
        ]);

        $outFp = @fopen($outPath, 'w');
        if (!$outFp) {
            $backup->update(['status' => 'failed']);
            throw new \RuntimeException('Could not open output file: ' . $outPath);
        }

        $proc = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => $outFp,
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($proc)) {
            fclose($outFp);
            $backup->update(['status' => 'failed']);
            throw new \RuntimeException('Could not launch mysqldump. Check that MySQL is installed.');
        }

        fclose($pipes[0]);
        $errOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        fclose($outFp);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0) {
            @unlink($outPath);
            $backup->update(['status' => 'failed']);
            throw new \RuntimeException($errOutput ? trim($errOutput) : "mysqldump exited with code $exitCode");
        }

        clearstatcache(true, $outPath);
        $fileSize = filesize($outPath);
        $backup->update([
            'file_size' => $fileSize,
            'status' => 'completed',
        ]);

        return $backup;
    }
}
