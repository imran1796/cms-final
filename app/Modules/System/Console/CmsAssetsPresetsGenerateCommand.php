<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CmsAssetsPresetsGenerateCommand extends Command
{
    protected $signature = 'cms:assets:presets:generate {--preset= : Only one preset key} {--rebuild : Rebuild variants}';
    protected $description = 'Generate asset variants for presets (minimal)';

    public function handle(): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('media')) {
                $this->warn('media table not found; skipping');
                return self::SUCCESS;
            }

            $preset = (string) ($this->option('preset') ?? '');
            $rebuild = (bool) $this->option('rebuild');

            $q = DB::table('media')->where('mime', 'like', 'image/%');

            $count = 0;
            foreach ($q->get() as $m) {
                if ($rebuild) {
                    DB::table('media_variants')->where('media_id', (int) $m->id)->delete();
                }

                $presetKey = $preset ?: 'default';
                DB::table('media_variants')->updateOrInsert(
                    [
                        'media_id' => (int) $m->id,
                        'transform_key' => $presetKey,
                    ],
                    [
                        'preset_key' => $presetKey,
                        'path' => '',
                        'meta' => json_encode(['generated_by' => 'cli', 'preset' => $presetKey]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $count++;
            }

            $this->info("Presets generate done. touched={$count}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
