<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * The project category table for one company.
     */
    public function index(Request $request): View
    {
        $companies = Company::orderBy('name')->get();

        $company = $request->filled('company')
            ? $companies->firstWhere('id', (int) $request->query('company'))
            : null;
        $company ??= $companies->first();

        $categories = collect();
        $templates = collect();
        $mapped = collect();

        if ($company) {
            $categories = $company->categories()->orderBy('sort_order')->orderBy('code')->get();
            $templates = $company->templates()->with('segments')->get();

            // Free-text segments whose charset matches the shape of a category
            // code are the ones a category feeds in practice.
            $mapped = $templates->flatMap(
                fn ($t) => $t->segments
                    ->where('type', 'free')
                    ->map(fn ($s) => ['template' => $t, 'segment' => $s])
            )->values();
        }

        // Duplicate and blank codes are worth surfacing before they reach a number
        $dupes = $categories->groupBy(fn ($c) => strtoupper((string) $c->code))
            ->filter(fn ($g) => $g->count() > 1)
            ->keys()
            ->all();

        return view('categories.index', [
            'companies' => $companies,
            'company' => $company,
            'categories' => $categories,
            'templates' => $templates,
            'mapped' => $mapped,
            'dupes' => $dupes,
            'blank' => $categories->filter(fn ($c) => trim((string) $c->code) === '')->count(),
            'totalCount' => $categories->count(),
            'activeCount' => $categories->where('is_active', true)->filter(fn ($c) => $c->code)->count(),
        ]);
    }

    /**
     * Add one blank row, the way the prototype's "+ Add row" does.
     */
    public function store(Request $request, Company $company): RedirectResponse
    {
        $company->categories()->create([
            'code' => '',
            'name' => '',
            'note' => null,
            'is_active' => true,
            'sort_order' => (int) $company->categories()->max('sort_order') + 1,
        ]);

        // No toast: the new empty row is its own feedback
        return back();
    }

    /**
     * Save every row of the inline table at once.
     */
    public function saveAll(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.id' => ['required', 'integer'],
            'rows.*.code' => ['nullable', 'string', 'max:20'],
            'rows.*.name' => ['nullable', 'string', 'max:255'],
            'rows.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $owned = $company->categories()->pluck('id')->all();

        // Validate every row before writing any of them. Two rows swapping
        // codes is legal overall but collides halfway through, so the unique
        // index must not see a partial batch.
        $clean = [];
        $seen = [];

        foreach ($data['rows'] ?? [] as $row) {
            $id = (int) $row['id'];
            if (! in_array($id, $owned, true)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['code'] ?? '')));

            // A blank code is allowed — such a row is simply left out of the
            // pick lists, which the table warns about.
            if ($code !== '' && ! preg_match('/^[A-Z0-9_-]{1,20}$/', $code)) {
                return back()->withErrors([
                    'rows' => 'Code "'.$row['code'].'" may only use letters, digits, hyphen and underscore.',
                ]);
            }

            if ($code !== '' && in_array($code, $seen, true)) {
                return back()->withErrors([
                    'rows' => 'Code '.$code.' is used by more than one row. Codes must be unique within a company.',
                ]);
            }

            if ($code !== '') {
                $seen[] = $code;
            }

            $clean[] = [
                'id' => $id,
                'code' => $code,
                'name' => trim((string) ($row['name'] ?? '')),
                'note' => trim((string) ($row['note'] ?? '')) ?: null,
            ];
        }

        DB::transaction(function () use ($clean) {
            // Park every code first, so a swap between two rows cannot trip
            // the unique index on its way through.
            foreach ($clean as $row) {
                Category::whereKey($row['id'])->update(['code' => '__tmp_'.$row['id']]);
            }

            foreach ($clean as $row) {
                Category::whereKey($row['id'])->update([
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'note' => $row['note'],
                ]);
            }
        });

        return back()->with('status', 'Categories saved');
    }

    /**
     * Toggle a row on or off. Switching a code off is the safe way to retire
     * it: numbers already issued with it stay readable.
     */
    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        // No toast: the checkbox and the struck-through row already show it
        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $code = $category->code;
        $category->delete();

        return back()->with('status', 'Removed '.($code ?: 'row'));
    }

    /**
     * Paste rows from a spreadsheet — CODE | Name | Description
     */
    public function bulk(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'string', 'max:20000'],
        ], [
            'rows.required' => 'Nothing to add — paste at least one row.',
        ]);

        $existing = $company->categories()->pluck('code')
            ->map(fn ($c) => strtoupper((string) $c))->all();
        $order = (int) $company->categories()->max('sort_order');

        $added = 0;
        $skipped = 0;
        $seen = [];

        foreach (preg_split('/\r\n|\r|\n/', $data['rows']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Accept pipe, comma or tab so a paste straight out of Excel works
            $parts = preg_split('/\s*[|,\t]\s*/', $line);
            $code = strtoupper(trim($parts[0] ?? ''));

            if ($code === '' || ! preg_match('/^[A-Z0-9_-]{1,20}$/', $code)
                || in_array($code, $existing, true) || in_array($code, $seen, true)) {
                $skipped++;
                continue;
            }

            $seen[] = $code;

            $company->categories()->create([
                'code' => $code,
                'name' => trim($parts[1] ?? '') !== '' ? trim($parts[1]) : $code,
                'note' => trim($parts[2] ?? '') !== '' ? trim($parts[2]) : null,
                'is_active' => true,
                'sort_order' => ++$order,
            ]);

            $added++;
        }

        $msg = $added.' row'.($added === 1 ? '' : 's').' added';
        if ($skipped > 0) {
            $msg .= ' · '.$skipped.' skipped (duplicate code or bad format)';
        }

        return back()->with('status', $msg);
    }

    public function reorder(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data, $company) {
            foreach ($data['order'] as $i => $id) {
                $company->categories()->whereKey($id)->update(['sort_order' => $i + 1]);
            }
        });

        return back()->with('status', 'Order saved');
    }
}
