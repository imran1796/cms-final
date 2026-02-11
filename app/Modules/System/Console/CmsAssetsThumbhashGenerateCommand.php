<?php

namespace App\Modules\System\Console;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Services\ThumbhashGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CmsAssetsThumbhashGenerateCommand extends Command
{
    protected $signature = 'cms:assets:thumbhash:generate {--limit=200 : Max rows}';
    protected $description = 'Generate Thumbhash for image media using srwiez/thumbhash';

    public function handle(): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('media')) {
                $this->warn('media table not found; skipping');
                return self::SUCCESS;
            }

            $limit = (int) $this->option('limit');

            $rows = DB::table('media')
                ->where('mime', 'like', 'image/%')
                ->limit($limit)
                ->get();

            $count = 0;

            foreach ($rows as $m) {
                $disk = $m->disk ?? config('cms_assets.disk', 'local');
                $path = Storage::disk($disk)->path($m->path);
                $thumbhash = ThumbhashGenerator::generate($path);

                if ($thumbhash === null) {
                    continue;
                }

                $meta = [];
                if (!empty($m->meta)) {
                    $decoded = json_decode((string) $m->meta, true);
                    if (is_array($decoded)) {
                        $meta = $decoded;
                    }
                }

                $meta['thumbhash'] = $thumbhash;

                DB::table('media')->where('id', (int) $m->id)->update([
                    'meta' => json_encode($meta),
                    'updated_at' => now(),
                ]);

                $count++;
            }

            $this->info("Thumbhash generated. updated={$count}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
