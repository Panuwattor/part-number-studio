<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\PartNumberFormat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('companies', 'code')],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.regex' => 'Company code may only use letters and digits.',
            'code.unique' => 'That company code is already in use.',
        ]);

        $company = Company::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'note' => $data['note'] ?? null,
            'is_active' => true,
        ]);

        // Start with one template so the company opens on a working number
        $template = $company->templates()->create([
            'name' => 'Main template',
            'code' => $company->code,
            'note' => 'Starter template — edit to suit',
            'separator' => PartNumberFormat::SEPARATOR_FALLBACK,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $template->segments()->createMany([
            [
                'label' => 'Company code',
                'type' => 'fixed',
                'position' => 1,
                'fixed_value' => $company->code,
            ],
            [
                'label' => 'Sequence',
                'type' => 'free',
                'position' => 2,
                'min_length' => 4,
                'max_length' => 4,
                'charset' => '0-9',
            ],
        ]);

        return redirect()
            ->route('categories.index', ['company' => $company->id])
            ->with('status', 'Company '.$company->name.' created');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('companies', 'code')->ignore($company->id),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.regex' => 'Company code may only use letters and digits.',
            'code.unique' => 'That company code is already in use.',
        ]);

        $company->update([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Company saved');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if (Company::count() < 2) {
            return back()->withErrors(['company' => 'Keep at least one company.']);
        }

        $name = $company->name;
        // Categories and templates cascade, per the migration
        $company->delete();

        return redirect()
            ->route('categories.index')
            ->with('status', 'Deleted '.$name);
    }
}
