<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class PartNumberStudioSeeder extends Seeder
{
    public function run(): void
    {
        $tpi = Company::create([
            'name' => 'Thai Part Industries',
            'code' => 'TPI',
            'note' => 'Machine part manufacturing',
            'is_active' => true,
        ]);

        $categories = [
            ['MTR', 'Motor', 'Drive motors and sub-assemblies', true],
            ['BRG', 'Bearing', 'Ball and roller bearings', true],
            ['SHF', 'Shaft', 'Turned shafts and spindles', true],
            ['HSG', 'Housing', 'Cast and machined housings', true],
            ['GBX', 'Gearbox', 'Retired in 2024 — kept for old numbers', false],
        ];

        foreach ($categories as $i => [$code, $name, $note, $active]) {
            $tpi->categories()->create([
                'code' => $code,
                'name' => $name,
                'note' => $note,
                'is_active' => $active,
                'sort_order' => $i + 1,
            ]);
        }

        // TPI-MTR-0001 — a company prefix, a category code, then a sequence
        $fg = $tpi->templates()->create([
            'name' => 'Finished goods',
            'code' => 'FG',
            'note' => 'Company prefix, category, then a sequence typed in by the user',
            'separator' => '-',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $fg->segments()->createMany([
            ['label' => 'Company code', 'type' => 'fixed', 'position' => 1, 'fixed_value' => 'TPI'],
            ['label' => 'Category', 'type' => 'free', 'position' => 2, 'min_length' => 3, 'max_length' => 3, 'charset' => 'A-Z'],
            ['label' => 'Sequence', 'type' => 'free', 'position' => 3, 'min_length' => 4, 'max_length' => 4, 'charset' => '0-9'],
        ]);

        // Underscores throughout, so a material number never reads like a finished part
        $rm = $tpi->templates()->create([
            'name' => 'Raw materials',
            'code' => 'RM',
            'note' => 'Underscores by default, so this never reads like a finished part',
            'separator' => '_',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $rm->segments()->createMany([
            ['label' => 'Prefix', 'type' => 'fixed', 'position' => 1, 'fixed_value' => 'RM'],
            ['label' => 'Material', 'type' => 'free', 'position' => 2, 'min_length' => 3, 'max_length' => 3, 'charset' => 'A-Z'],
            // One join overrides the template default, so the number mixes _ and -
            ['label' => 'Grade', 'type' => 'free', 'position' => 3, 'separator' => '-', 'min_length' => 2, 'max_length' => 4, 'charset' => 'A-Z0-9'],
        ]);

        $sp = Company::create([
            'name' => 'Siam Precision Co.',
            'code' => 'SP',
            'note' => 'Precision turning and milling',
            'is_active' => true,
        ]);

        foreach ([
            ['CNC', 'CNC mill', '3- and 5-axis milling', true],
            ['LTH', 'Lathe', 'Turning centres', true],
            ['PRS', 'Press', 'Stamping presses', true],
            ['EDM', 'Wire EDM', null, true],
        ] as $i => [$code, $name, $note, $active]) {
            $sp->categories()->create([
                'code' => $code,
                'name' => $name,
                'note' => $note,
                'is_active' => $active,
                'sort_order' => $i + 1,
            ]);
        }

        $dwg = $sp->templates()->create([
            'name' => 'Drawing parts',
            'code' => 'DWG',
            'note' => 'Drawing number and revision, so drawings stay traceable',
            'separator' => '-',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $dwg->segments()->createMany([
            ['label' => 'Company code', 'type' => 'fixed', 'position' => 1, 'fixed_value' => 'SP'],
            ['label' => 'Drawing no.', 'type' => 'free', 'position' => 2, 'min_length' => 6, 'max_length' => 6, 'charset' => '0-9'],
            ['label' => 'Revision', 'type' => 'free', 'position' => 3, 'min_length' => 1, 'max_length' => 2, 'charset' => 'A-Z'],
        ]);
    }
}
