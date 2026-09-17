<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Template;
use Illuminate\Support\Collection;

/**
 * Matches imported part numbers back to the template that produced them.
 *
 * With only fixed and free-text segments, two templates can easily share a
 * shape, so a tie is broken by how much literal structure each template pins
 * down: fixed text is a stronger claim than free text of the same length.
 */
class PartNumberMatcher
{
    /**
     * How much literal structure a template pins down, ignoring any code.
     * Fixed text counts triple; free text pins down nothing but its length.
     *
     * This ranks templates on their own. Ranking two templates that both
     * accept a given code is done by {@see fitness}, which can see the values.
     */
    public static function specificity(Template $template): int
    {
        $score = 0;

        foreach ($template->segments as $segment) {
            if ($segment->type === 'fixed') {
                $score += mb_strlen((string) $segment->fixed_value) * 3;
                continue;
            }

            // A fixed-length free segment is a slightly stronger claim than a
            // variable-length one, and a narrow charset stronger than a wide one.
            $min = (int) ($segment->min_length ?: 1);
            $max = (int) ($segment->max_length ?: $min);

            if ($min === $max) {
                $score += 1;
            }

            if (($segment->charset ?? 'A-Z0-9') !== 'A-Z0-9') {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * How tightly a template fits the code it just matched.
     *
     * Several templates can accept the same code, so the winner is the one
     * that leaves the least slack — the narrowest rule the value still
     * satisfies, counted per segment and summed:
     *
     *   fixed text      the strongest claim there is, 10 per character
     *   charset         narrower beats wider, but only when the value could
     *                   have gone in the wider one too
     *   length window   a window that pins the value exactly beats one that
     *                   merely contains it
     *
     * Scores are per character, so a longer segment carries more weight than a
     * short one, and a template cannot win by simply having more segments.
     */
    public static function fitness(Template $template, array $matches): int
    {
        $score = 0;

        foreach ($template->segments as $i => $segment) {
            $value = (string) ($matches[$i + 1] ?? '');
            $length = mb_strlen($value);

            if ($segment->type === 'fixed') {
                $score += mb_strlen((string) $segment->fixed_value) * 10;
                continue;
            }

            // A narrow charset that still accepts this value is a tighter fit
            // than a wide one. Weighted by length so a 6-digit run outranks a
            // 2-digit one rather than both counting the same.
            $charset = $segment->charset ?: 'A-Z0-9';
            $width = PartNumberFormat::CHARSETS[$charset]['width']
                ?? PartNumberFormat::CHARSETS['A-Z0-9']['width'];

            $score += (int) round($length * (36 - $width) / 26 * 4);

            // A length window that pins the value exactly is a stronger claim
            // than one that merely contains it.
            $min = (int) ($segment->min_length ?: 1);
            $max = (int) ($segment->max_length ?: $min);
            $slack = $max - $min;

            $score += $slack === 0 ? 6 : max(0, 6 - $slack);
        }

        return $score;
    }

    /**
     * Split a matched code into its segments, with a note per segment.
     */
    public static function decode(Template $template, array $matches): array
    {
        $out = [];

        foreach ($template->segments as $i => $segment) {
            $value = $matches[$i + 1] ?? '';
            $note = '';

            if ($segment->type === 'fixed') {
                $note = 'fixed';
            } else {
                $charset = PartNumberFormat::CHARSETS[$segment->charset ?: 'A-Z0-9']['label'] ?? '';
                $note = mb_strlen($value).' chars · '.$charset;
            }

            $out[] = [
                'label' => $segment->label,
                'type' => $segment->type,
                'value' => $value,
                'note' => $note,
            ];
        }

        return $out;
    }

    /**
     * Match one code against every template of a company.
     *
     * Status is one of:
     *   matched   — one template fits the code better than the rest
     *   ambiguous — several templates fit it equally well
     *   none      — no template accepts it
     */
    public static function match(Collection $templates, string $raw): array
    {
        $code = trim($raw);
        $hits = [];

        foreach ($templates as $template) {
            if ($template->segments->isEmpty()) {
                continue;
            }

            $pattern = PartNumberFormat::regex($template, true);

            // A malformed pattern must not abort the whole import
            if (@preg_match($pattern, $code, $m) !== 1) {
                continue;
            }

            $hits[] = [
                'template' => $template,
                'decoded' => self::decode($template, $m),
                // How well this template fits the code in hand
                'score' => self::fitness($template, $m),
                // How much the template pins down regardless of any code
                'specificity' => self::specificity($template),
            ];
        }

        if (! $hits) {
            return ['code' => $code, 'status' => 'none', 'hit' => null, 'tied' => []];
        }

        // Best fit first; where two fit equally well, the template that pins
        // down more structure wins.
        usort($hits, fn ($a, $b) => [$b['score'], $b['specificity']] <=> [$a['score'], $a['specificity']]);

        $best = $hits[0];
        $tied = array_values(array_filter(
            $hits,
            fn ($h) => $h['score'] === $best['score'] && $h['specificity'] === $best['specificity']
        ));

        return [
            'code' => $code,
            'status' => count($tied) > 1 ? 'ambiguous' : 'matched',
            'hit' => $hits[0],
            'tied' => $tied,
        ];
    }

    /**
     * Match a whole pasted blob. Accepts one code per line, or separated by
     * commas or semicolons, and strips surrounding quotes from spreadsheets.
     */
    public static function matchAll(Company $company, string $blob): array
    {
        return self::matchList($company, preg_split('/[\r\n,;]+/', $blob) ?: []);
    }

    /**
     * Match a list of codes — from a pasted blob or a spreadsheet column.
     * Templates are loaded once for the whole list, not once per code.
     */
    public static function matchList(Company $company, iterable $codes): array
    {
        $templates = $company->templates()->with('segments')->get();

        $clean = collect($codes)
            ->map(fn ($c) => trim(trim((string) $c), "\"'"))
            ->filter()
            ->values();

        return $clean->map(fn ($c) => self::match($templates, $c))->all();
    }
}
