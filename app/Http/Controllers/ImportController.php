<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\PartNumberFormat as Fmt;
use App\Support\PartNumberMatcher;
use App\Support\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ImportController extends Controller
{
    /** Uploaded sheets are scratch data, kept only until the match is done. */
    private const DISK = 'local';

    private const DIR = 'imports';

    /**
     * Paste numbers, or upload a spreadsheet and pick the column that holds
     * them, then match everything against the company's templates.
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

        // A sheet waiting for a column choice, carried between requests by key
        $sheet = null;
        $codes = [];

        if ($key = $request->input('sheet')) {
            $sheet = $this->loadSheet($key);
        }

        $firstRowIsHeader = $request->boolean('header', true);
        $codeColumn = $request->filled('column') ? (int) $request->input('column') : null;

        if ($sheet && $codeColumn !== null) {
            // Pull the chosen column out of the stored rows
            $rows = $sheet['rows'];
            if ($firstRowIsHeader) {
                array_shift($rows);
            }

            foreach ($rows as $row) {
                $value = trim((string) ($row[$codeColumn] ?? ''));
                if ($value !== '') {
                    $codes[] = $value;
                }
            }
        }

        // Pasted text is matched the same way, so both routes share one result
        $blob = (string) $request->input('codes', '');
        if (trim($blob) !== '') {
            $codes = array_merge($codes, preg_split('/[\r\n,;]+/', $blob) ?: []);
        }

        $results = [];
        if ($company && $codes) {
            $results = PartNumberMatcher::matchList($company, $codes);
        }

        $tally = ['matched' => 0, 'ambiguous' => 0, 'none' => 0];
        $byTemplate = [];

        foreach ($results as $r) {
            $tally[$r['status']]++;

            if ($r['hit']) {
                $name = $r['hit']['template']->name;
                $byTemplate[$name] = ($byTemplate[$name] ?? 0) + 1;
            }
        }

        return view('import.index', [
            'companies' => $companies,
            'company' => $company,
            'templates' => $templates,
            'totalCount' => $company ? $company->categories()->count() : 0,
            'blob' => $blob,
            'sheet' => $sheet,
            'sheetKey' => $sheet ? $request->input('sheet') : null,
            'firstRowIsHeader' => $firstRowIsHeader,
            'codeColumn' => $codeColumn,
            'results' => $results,
            'tally' => $tally,
            'byTemplate' => $byTemplate,
        ]);
    }

    /**
     * Take the upload, read it once, and park the rows so picking a different
     * column afterwards does not mean uploading again.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'company' => ['required', 'integer', 'exists:companies,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xlsm,xls,csv,txt,ods'],
        ], [
            'file.mimes' => 'Upload an Excel file (.xlsx, .xls, .ods) or a CSV.',
            'file.max' => 'The file is larger than 10 MB.',
        ]);

        $file = $request->file('file');

        try {
            $sheet = SpreadsheetReader::read(
                $file->getRealPath(),
                $file->getClientOriginalExtension()
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'file' => 'That file could not be read. Save it as .xlsx or .csv and try again.',
            ]);
        }

        if (! $sheet['rows']) {
            return back()->withErrors(['file' => 'The first sheet of that file is empty.']);
        }

        $this->sweepOldSheets();

        $key = bin2hex(random_bytes(8));

        Storage::disk(self::DISK)->put(
            self::DIR."/{$key}.json",
            json_encode([
                'name' => $file->getClientOriginalName(),
                'sheet' => $sheet['sheet'],
                'headers' => $sheet['headers'],
                'rows' => $sheet['rows'],
                'truncated' => $sheet['truncated'],
            ], JSON_UNESCAPED_UNICODE)
        );

        // Guess the column that looks most like part numbers, so the common
        // case needs no extra click.
        $guess = $this->guessCodeColumn($sheet['rows']);

        return redirect()->route('import.index', [
            'company' => $request->integer('company'),
            'sheet' => $key,
            'header' => 1,
            'column' => $guess,
        ]);
    }

    /** Drop a parked sheet once the user is finished with it. */
    public function discard(Request $request): RedirectResponse
    {
        if ($key = $request->input('sheet')) {
            $this->forgetSheet($key);
        }

        return redirect()->route('import.index', ['company' => $request->input('company')]);
    }

    /**
     * Load one example code per template, so the matcher can be tried
     * without any file at hand, plus one code that matches nothing.
     */
    public function sample(Request $request, Company $company): View
    {
        $lines = $company->templates()->with('segments')->get()
            ->filter(fn ($t) => $t->segments->isNotEmpty())
            ->map(fn ($t) => Fmt::sample($t))
            ->push('ZZ-ZZZ-9999')
            ->implode("\n");

        $request->merge(['codes' => $lines, 'company' => $company->id]);

        return $this->index($request);
    }

    /* ---------------- helpers ---------------- */

    private function loadSheet(string $key): ?array
    {
        // The key comes from the query string, so it must not walk the disk
        if (! preg_match('/^[a-f0-9]{16}$/', $key)) {
            return null;
        }

        $path = self::DIR."/{$key}.json";

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return json_decode(Storage::disk(self::DISK)->get($path), true) ?: null;
    }

    private function forgetSheet(string $key): void
    {
        if (preg_match('/^[a-f0-9]{16}$/', $key)) {
            Storage::disk(self::DISK)->delete(self::DIR."/{$key}.json");
        }
    }

    /**
     * Parked sheets are scratch data. Most get discarded by hand, but a
     * closed tab leaves one behind, so anything older than a day goes.
     */
    private function sweepOldSheets(): void
    {
        $disk = Storage::disk(self::DISK);
        $cutoff = now()->subDay()->getTimestamp();

        foreach ($disk->files(self::DIR) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }
    }

    /**
     * The column whose cells most often look like a part number — mixed
     * letters, digits and separators, with no spaces.
     */
    private function guessCodeColumn(array $rows): int
    {
        $sample = array_slice($rows, 1, 30) ?: $rows;
        $best = 0;
        $bestScore = -1;
        $width = max(array_map('count', $sample) ?: [1]);

        for ($c = 0; $c < $width; $c++) {
            $score = 0;

            foreach ($sample as $row) {
                $v = trim((string) ($row[$c] ?? ''));

                if ($v === '' || str_contains($v, ' ')) {
                    continue;
                }

                // Codes are short, uppercase-ish, and often carry - or _
                if (preg_match('/^[A-Za-z0-9]+([-_][A-Za-z0-9]+)+$/', $v)) {
                    $score += 3;
                } elseif (preg_match('/^[A-Za-z0-9]{4,20}$/', $v) && preg_match('/[A-Za-z]/', $v) && preg_match('/\d/', $v)) {
                    $score += 2;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }

        return $best;
    }
}
