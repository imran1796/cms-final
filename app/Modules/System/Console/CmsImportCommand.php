<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CmsImportCommand extends Command
{
    protected $signature = 'cms:import {file : Path relative to storage/app OR absolute path}';
    protected $description = 'Import a CMS export JSON';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        try {
            $json = $this->readFile($file);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            DB::beginTransaction();

            foreach (($data['spaces'] ?? []) as $s) {
                DB::table('spaces')->updateOrInsert(
                    ['id' => $s['id']],
                    [
                        'handle' => $s['handle'],
                        'name' => $s['name'],
                        'settings' => $s['settings'] ?? json_encode([]),
                        'storage_prefix' => $s['storage_prefix'] ?? null,
                        'created_at' => $s['created_at'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            foreach (($data['collections'] ?? []) as $c) {
                DB::table('collections')->updateOrInsert(
                    ['id' => $c['id']],
                    [
                        'space_id' => $c['space_id'],
                        'handle' => $c['handle'],
                        'type' => $c['type'],
                        'fields' => $c['fields'] ?? json_encode([]),
                        'settings' => $c['settings'] ?? json_encode([]),
                        'created_at' => $c['created_at'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            foreach (($data['entries'] ?? []) as $e) {
                DB::table('entries')->updateOrInsert(
                    ['id' => $e['id']],
                    [
                        'space_id' => $e['space_id'],
                        'collection_id' => $e['collection_id'],
                        'status' => $e['status'] ?? 'draft',
                        'published_at' => $e['published_at'] ?? null,
                        'data' => $e['data'] ?? json_encode([]),
                        'created_at' => $e['created_at'] ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->optionalImport('forms', $data['forms'] ?? []);
            $this->optionalImport('form_submissions', $data['form_submissions'] ?? []);
            $this->optionalImport('media', $data['media'] ?? []);
            $this->optionalImport('media_variants', $data['media_variants'] ?? []);

            DB::commit();

            $this->info('Import completed');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function readFile(string $file): string
    {
        if (is_file($file)) {
            return (string) file_get_contents($file);
        }

        return Storage::disk('local')->get($file);
    }

    private function optionalImport(string $table, array $rows): void
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return;
            }
            foreach ($rows as $r) {
                if (!isset($r['id'])) {
                    continue;
                }
                DB::table($table)->updateOrInsert(['id' => $r['id']], $r);
            }
        } catch (\Throwable) {
        }
    }
}
