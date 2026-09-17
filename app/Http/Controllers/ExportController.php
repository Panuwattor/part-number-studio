<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    /**
     * Download every company, category and template as one JSON file.
     */
    public function __invoke(): Response
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'companies' => Company::with(['categories', 'templates.segments'])
                ->orderBy('name')
                ->get()
                ->map(fn ($company) => [
                    'name' => $company->name,
                    'code' => $company->code,
                    'note' => $company->note,
                    'is_active' => $company->is_active,
                    'categories' => $company->categories->map(fn ($c) => [
                        'code' => $c->code,
                        'name' => $c->name,
                        'note' => $c->note,
                        'is_active' => $c->is_active,
                        'sort_order' => $c->sort_order,
                    ])->values(),
                    'templates' => $company->templates->map(fn ($t) => [
                        'name' => $t->name,
                        'code' => $t->code,
                        'note' => $t->note,
                        'separator' => $t->separator,
                        'is_active' => $t->is_active,
                        'segments' => $t->segments->map(fn ($s) => [
                            'label' => $s->label,
                            'type' => $s->type,
                            'position' => $s->position,
                            'separator' => $s->separator,
                            'fixed_value' => $s->fixed_value,
                            'min_length' => $s->min_length,
                            'max_length' => $s->max_length,
                            'charset' => $s->charset,
                        ])->values(),
                    ])->values(),
                ])->values(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'part-number-studio-'.now()->format('Ymd-His').'.json';

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
