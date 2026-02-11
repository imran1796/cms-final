<?php

namespace App\Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CmsExportCommand extends Command
{
    protected $signature = 'cms:export {space? : Space id or handle (optional)}';
    protected $description = 'Export spaces/collections/entries/forms/media to a JSON file';

    public function handle(): int
    {
        try {
            $spaceArg = $this->argument('space');

            $spaces = $spaceArg
                ? $this->loadSpacesFiltered((string) $spaceArg)
                : DB::table('spaces')->get()->all();

            $spaceIds = array_map(fn($s) => (int) $s->id, $spaces);

            $payload = [
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'spaces' => $spaces,
                'collections' => DB::table('collections')->whereIn('space_id', $spaceIds)->get()->all(),
                'entries' => DB::table('entries')->whereIn('space_id', $spaceIds)->get()->all(),
            ];

            if ($this->tableExists('forms')) {
                $payload['forms'] = DB::table('forms')->whereIn('space_id', $spaceIds)->get()->all();
            }
            if ($this->tableExists('form_submissions')) {
                $payload['form_submissions'] = DB::table('form_submissions')->whereIn('space_id', $spaceIds)->get()->all();
            }
            if ($this->tableExists('media')) {
                $payload['media'] = DB::table('media')->whereIn('space_id', $spaceIds)->get()->all();
            }
            if ($this->tableExists('media_variants')) {
                $payload['media_variants'] = DB::table('media_variants')
                    ->join('media', 'media_variants.media_id', '=', 'media.id')
                    ->whereIn('media.space_id', $spaceIds)
                    ->select('media_variants.*')
                    ->get()
                    ->all();
            }

            $dir = 'exports';
            $name = 'cms_export_' . now()->format('Ymd_His') . '.json';
            $path = "{$dir}/{$name}";

            Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT));

            $this->info("Export written: storage/app/{$path}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Export failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function loadSpacesFiltered(string $spaceArg): array
    {
        if (ctype_digit($spaceArg)) {
            $space = DB::table('spaces')->where('id', (int) $spaceArg)->first();
            return $space ? [$space] : [];
        }

        $space = DB::table('spaces')->where('handle', $spaceArg)->first();
        return $space ? [$space] : [];
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
