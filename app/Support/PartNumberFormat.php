<?php

namespace App\Support;

use App\Models\Template;
use App\Models\TemplateSegment;

/**
 * How a part number is assembled from a template.
 *
 * Two segment types only:
 *   fixed — always the same characters, such as a company prefix
 *   free  — typed in by the user, within the length and charset rules set here
 */
class PartNumberFormat
{
    /**
     * Separators are punctuation between segments, repeatable up to four.
     * Real part numbers use more than hyphens: spaces and dots are common in
     * exported data, so anything non-alphanumeric is allowed through.
     */
    public const SEPARATOR_MAX = 4;

    public const SEPARATOR_PRESETS = ['-', '--', '_', '__', ' ', ' - ', '.', '/'];

    public const SEPARATOR_FALLBACK = '-';

    /**
     * Charsets are ordered narrowest first. A narrower set is a stronger claim
     * on a value, which is what breaks a tie between two matching templates.
     */
    public const CHARSETS = [
        '0-9' => ['label' => 'Digits only', 'regex' => '[0-9]', 'width' => 10],
        'A-Z' => ['label' => 'Letters only', 'regex' => '[A-Za-z]', 'width' => 26],
        'A-Z0-9' => ['label' => 'Letters and digits', 'regex' => '[A-Za-z0-9]', 'width' => 36],
    ];

    public const TYPES = [
        'fixed' => ['label' => 'Fixed text', 'hint' => 'Always the same characters, such as a company prefix'],
        'free' => ['label' => 'Free text', 'hint' => 'Typed in by the user within the rules you set'],
    ];

    /**
     * Keep the punctuation, drop the rest, then cap the length.
     * Letters and digits are segment content, never separators, so they go.
     */
    public static function cleanSeparator(?string $value): string
    {
        return mb_substr(preg_replace('/[\p{L}\p{N}]/u', '', (string) $value), 0, self::SEPARATOR_MAX);
    }

    /**
     * The separator in front of a segment.
     * A per-segment override wins over the template default.
     */
    public static function separatorBefore(Template $template, TemplateSegment $segment, int $index): string
    {
        if ($index === 0) {
            return '';
        }

        if ($own = self::cleanSeparator($segment->separator)) {
            return $own;
        }

        return self::cleanSeparator($template->separator) ?: self::SEPARATOR_FALLBACK;
    }

    /** The character a free segment stands in as, so the shape reads like the real thing. */
    public static function placeholder(TemplateSegment $segment): string
    {
        return $segment->charset === '0-9' ? '0' : 'A';
    }

    /** The shape of one segment, shown in the pattern. */
    public static function segmentMask(TemplateSegment $segment): string
    {
        if ($segment->type === 'fixed') {
            return (string) $segment->fixed_value;
        }

        $min = (int) ($segment->min_length ?: 1);
        $max = (int) ($segment->max_length ?: $min);
        $char = self::placeholder($segment);

        // Fixed length shows as one character per position; a variable one trails off.
        return $min === $max ? str_repeat($char, $min) : str_repeat($char, $min).'…';
    }

    /** Fixed length of a segment, or null when it varies. */
    public static function segmentLength(TemplateSegment $segment): ?int
    {
        if ($segment->type === 'fixed') {
            return mb_strlen((string) $segment->fixed_value);
        }

        $min = (int) ($segment->min_length ?: 1);
        $max = (int) ($segment->max_length ?: $min);

        return $min === $max ? $min : null;
    }

    /** Pattern for the whole template, such as  AB-ABC-AA */
    public static function mask(Template $template): string
    {
        $out = '';
        foreach ($template->segments as $i => $segment) {
            $out .= self::separatorBefore($template, $segment, $i).self::segmentMask($segment);
        }

        return $out;
    }

    /** Total length of the number, or null when any segment varies. */
    public static function totalLength(Template $template): ?int
    {
        $total = 0;
        foreach ($template->segments as $i => $segment) {
            $length = self::segmentLength($segment);
            if ($length === null) {
                return null;
            }
            $total += mb_strlen(self::separatorBefore($template, $segment, $i)) + $length;
        }

        return $total;
    }

    /**
     * Regex that tests whether a code was produced by this template.
     * With $capture, each segment becomes its own capture group so a matched
     * code can be split back into its parts.
     */
    public static function regex(Template $template, bool $capture = false): string
    {
        $out = '';

        foreach ($template->segments as $i => $segment) {
            $out .= preg_quote(self::separatorBefore($template, $segment, $i), '/');

            if ($segment->type === 'fixed') {
                $part = preg_quote((string) $segment->fixed_value, '/');
            } else {
                $charset = self::CHARSETS[$segment->charset] ?? self::CHARSETS['A-Z0-9'];
                $min = (int) ($segment->min_length ?: 1);
                $max = (int) ($segment->max_length ?: $min);
                $part = $charset['regex'].'{'.$min.','.$max.'}';
            }

            $out .= $capture ? '('.$part.')' : $part;
        }

        // Case-insensitive: jd14040031z and JD14040031Z are the same part.
        return '/^'.$out.'$/i';
    }

    /**
     * An example of a code this template would produce.
     * Free segments are filled with A or 0, depending on the charset.
     */
    public static function sample(Template $template): string
    {
        $out = '';

        foreach ($template->segments as $i => $segment) {
            $out .= self::separatorBefore($template, $segment, $i);

            if ($segment->type === 'fixed') {
                $out .= (string) $segment->fixed_value;
                continue;
            }

            $out .= str_repeat(self::placeholder($segment), max(1, (int) ($segment->min_length ?: 1)));
        }

        return $out;
    }
}
