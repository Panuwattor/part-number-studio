<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Template;
use App\Models\TemplateSegment;
use App\Support\PartNumberFormat as Fmt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    /**
     * The part number format designer.
     */
    public function index(Request $request): View
    {
        $companies = Company::orderBy('name')->get();

        $company = $request->filled('company')
            ? $companies->firstWhere('id', (int) $request->query('company'))
            : null;
        $company ??= $companies->first();

        $templates = $company
            ? $company->templates()->with('segments')->get()
            : collect();

        // The template being edited
        $template = $request->filled('template')
            ? $templates->firstWhere('id', (int) $request->query('template'))
            : null;
        $template ??= $templates->first();

        return view('templates.index', [
            'companies' => $companies,
            'company' => $company,
            'templates' => $templates,
            'template' => $template,
            // Drives the count shown on the category tab
            'totalCount' => $company ? $company->categories()->count() : 0,
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $template = $company->templates()->create([
            'name' => $data['name'],
            'code' => $data['code'] ? strtoupper($data['code']) : null,
            'note' => $data['note'] ?? null,
            'separator' => Fmt::SEPARATOR_FALLBACK,
            'is_active' => true,
            'sort_order' => (int) $company->templates()->max('sort_order') + 1,
        ]);

        // Start with two segments so the number reads as something
        $template->segments()->createMany([
            [
                'label' => 'Prefix',
                'type' => 'fixed',
                'position' => 1,
                'fixed_value' => strtoupper(substr($data['code'] ?: $company->code, 0, 3)),
            ],
            [
                'label' => 'Free text',
                'type' => 'free',
                'position' => 2,
                'min_length' => 4,
                'max_length' => 4,
                'charset' => 'A-Z0-9',
            ],
        ]);

        return redirect()
            ->route('templates.index', ['company' => $company->id, 'template' => $template->id])
            ->with('status', 'Template '.$template->name.' created');
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
            'separator' => ['nullable', 'string', 'max:4'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'name' => $data['name'],
            'code' => $data['code'] ? strtoupper($data['code']) : null,
            'note' => $data['note'] ?? null,
            'separator' => Fmt::cleanSeparator($data['separator'] ?? null) ?: Fmt::SEPARATOR_FALLBACK,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Template saved');
    }

    public function duplicate(Template $template): RedirectResponse
    {
        $copy = $template->replicate(['created_at', 'updated_at']);
        $copy->name = $template->name.' (copy)';
        $copy->sort_order = (int) $template->company->templates()->max('sort_order') + 1;
        $copy->save();

        foreach ($template->segments as $segment) {
            $new = $segment->replicate(['created_at', 'updated_at']);
            $new->template_id = $copy->id;
            $new->save();
        }

        return redirect()
            ->route('templates.index', ['company' => $template->company_id, 'template' => $copy->id])
            ->with('status', 'Template duplicated');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $companyId = $template->company_id;

        if ($template->company->templates()->count() < 2) {
            return back()->withErrors(['template' => 'Keep at least one template.']);
        }

        $name = $template->name;
        $template->delete();

        return redirect()
            ->route('templates.index', ['company' => $companyId])
            ->with('status', 'Deleted '.$name);
    }

    /* ---------------- segments ---------------- */

    public function addSegment(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:fixed,free'],
        ]);

        $isFixed = $data['type'] === 'fixed';

        $segment = $template->segments()->create([
            'label' => $isFixed ? 'Fixed text' : 'Free text',
            'type' => $data['type'],
            'position' => (int) $template->segments()->max('position') + 1,
            'fixed_value' => $isFixed ? 'AB' : null,
            'min_length' => $isFixed ? null : 3,
            'max_length' => $isFixed ? null : 3,
            'charset' => $isFixed ? null : 'A-Z0-9',
        ]);

        // No toast: the new segment card appears in place. It is appended at
        // the end of a list that can run past the fold, so land on it rather
        // than at the top of the page.
        return back()->withFragment('seg'.$segment->id);
    }

    /**
     * Save every segment of a template at once.
     */
    public function saveSegments(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'segments' => ['required', 'array'],
            'segments.*.id' => ['required', 'integer'],
            'segments.*.label' => ['required', 'string', 'max:255'],
            'segments.*.type' => ['required', 'in:fixed,free'],
            'segments.*.separator' => ['nullable', 'string', 'max:4'],
            'segments.*.fixed_value' => ['nullable', 'string', 'max:40'],
            'segments.*.min_length' => ['nullable', 'integer', 'min:1', 'max:40'],
            'segments.*.max_length' => ['nullable', 'integer', 'min:1', 'max:40'],
            'segments.*.charset' => ['nullable', 'in:A-Z0-9,0-9,A-Z'],
        ]);

        $owned = $template->segments()->pluck('id')->all();

        foreach ($data['segments'] as $i => $row) {
            if (! in_array((int) $row['id'], $owned, true)) {
                continue;
            }

            $isFixed = $row['type'] === 'fixed';

            // Fixed text must not be blank, or the segment vanishes from the number
            if ($isFixed && trim((string) ($row['fixed_value'] ?? '')) === '') {
                return back()->withErrors([
                    'segments' => 'Segment "'.$row['label'].'" is fixed text, so it needs a value.',
                ]);
            }

            $min = (int) ($row['min_length'] ?? 1);
            $max = (int) ($row['max_length'] ?? $min);

            // Minimum length must not exceed the maximum
            if (! $isFixed && $min > $max) {
                return back()->withErrors([
                    'segments' => 'Segment "'.$row['label'].'" has a minimum length above its maximum.',
                ]);
            }

            TemplateSegment::whereKey((int) $row['id'])->update([
                'label' => $row['label'],
                'type' => $row['type'],
                'position' => $i + 1,
                // The first segment carries no separator in front of it
                'separator' => $i === 0 ? null : (Fmt::cleanSeparator($row['separator'] ?? null) ?: null),
                'fixed_value' => $isFixed ? strtoupper(trim((string) $row['fixed_value'])) : null,
                'min_length' => $isFixed ? null : $min,
                'max_length' => $isFixed ? null : $max,
                'charset' => $isFixed ? null : ($row['charset'] ?? 'A-Z0-9'),
            ]);
        }

        return back()->with('status', 'Code structure saved');
    }

    public function moveSegment(TemplateSegment $segment, string $direction): RedirectResponse
    {
        $siblings = $segment->template->segments()->get();
        $index = $siblings->search(fn ($s) => $s->id === $segment->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $siblings->count()) {
            return back();
        }

        $other = $siblings[$target];

        // Swap the two positions
        $segmentPosition = $segment->position;
        $segment->update(['position' => $other->position]);
        $other->update(['position' => $segmentPosition]);

        // Whatever is now first must not carry a leading separator
        $segment->template->segments()->orderBy('position')->first()?->update(['separator' => null]);

        return back();
    }

    public function deleteSegment(TemplateSegment $segment): RedirectResponse
    {
        $segment->delete();

        return back()->with('status', 'Segment removed');
    }
}
