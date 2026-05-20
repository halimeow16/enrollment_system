<?php

namespace Database\Seeders;

use App\Models\EnrollmentTemplate;
use App\Models\IdTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PrebuiltTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/prebuilt_templates.json');

        if (! File::exists($path)) {
            return;
        }

        $data = json_decode(File::get($path), true) ?: [];

        foreach ($data['enrollment_templates'] ?? [] as $template) {
            $template['field_mappings'] = $this->decodeJsonValue($template['field_mappings'] ?? []);
            $template['is_active'] = ! EnrollmentTemplate::where('is_active', true)
                ->where('file_path', 'not like', 'templates/%')
                ->exists();

            EnrollmentTemplate::updateOrCreate(
                ['file_path' => $template['file_path']],
                $template
            );
        }

        foreach ($data['id_templates'] ?? [] as $template) {
            $template['layout_config'] = $this->decodeJsonValue($template['layout_config'] ?? []);
            $template['is_active'] = ! IdTemplate::where('side', $template['side'])
                ->where('is_active', true)
                ->where('background_image_path', 'not like', 'templates/%')
                ->exists();

            IdTemplate::updateOrCreate(
                [
                    'side' => $template['side'],
                    'background_image_path' => $template['background_image_path'],
                ],
                $template
            );
        }
    }

    private function decodeJsonValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode((string) $value, true) ?: [];
    }
}
