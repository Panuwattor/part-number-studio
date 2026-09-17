<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Template;
use App\Support\PartNumberFormat as Fmt;
use App\Support\PartNumberMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartNumberMatcherTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Acme',
            'code' => 'ACME',
            'is_active' => true,
        ]);
    }

    /**
     * Build a template from a compact spec.
     * A string segment is fixed text; an array is [charset, min, max].
     */
    private function template(string $name, array $segments): Template
    {
        $template = $this->company->templates()->create([
            'name' => $name,
            'separator' => '-',
            'is_active' => true,
        ]);

        foreach ($segments as $i => $spec) {
            if (is_string($spec)) {
                $template->segments()->create([
                    'label' => 'Fixed '.($i + 1),
                    'type' => 'fixed',
                    'position' => $i + 1,
                    'fixed_value' => $spec,
                ]);

                continue;
            }

            [$charset, $min, $max] = $spec;

            $template->segments()->create([
                'label' => 'Free '.($i + 1),
                'type' => 'free',
                'position' => $i + 1,
                'min_length' => $min,
                'max_length' => $max,
                'charset' => $charset,
            ]);
        }

        return $template->fresh('segments');
    }

    private function match(string $code): array
    {
        return PartNumberMatcher::matchList($this->company, [$code])[0];
    }

    public function test_digits_pick_the_digit_only_template_over_the_mixed_one(): void
    {
        $this->template('Mixed', ['ABC', ['A-Z0-9', 4, 4]]);
        $this->template('Digits', ['ABC', ['0-9', 4, 4]]);

        $result = $this->match('ABC-1234');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Digits', $result['hit']['template']->name);
    }

    public function test_letters_pick_the_letter_only_template_over_the_mixed_one(): void
    {
        $this->template('Mixed', ['ABC', ['A-Z0-9', 4, 4]]);
        $this->template('Letters', ['ABC', ['A-Z', 4, 4]]);

        $result = $this->match('ABC-WXYZ');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Letters', $result['hit']['template']->name);
    }

    public function test_a_truly_mixed_value_falls_back_to_the_mixed_template(): void
    {
        $this->template('Mixed', ['ABC', ['A-Z0-9', 4, 4]]);
        $this->template('Digits', ['ABC', ['0-9', 4, 4]]);
        $this->template('Letters', ['ABC', ['A-Z', 4, 4]]);

        $result = $this->match('ABC-A1B2');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Mixed', $result['hit']['template']->name);
    }

    /**
     * The narrow charset must win even when the rival template pins down more
     * fixed text. Shape alone must not outrank what the code actually holds.
     */
    public function test_narrow_charset_beats_a_template_carrying_more_fixed_text(): void
    {
        $this->template('LongPrefix', ['ACMEXY', ['A-Z0-9', 4, 4]]);
        $this->template('Digits', ['ACMEXY', ['0-9', 4, 4]]);

        $result = $this->match('ACMEXY-1234');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Digits', $result['hit']['template']->name);
    }

    /**
     * "Fewest characters that still satisfy the rule wins" — a tight length
     * window is a stronger claim than a loose one that merely contains it.
     */
    public function test_the_tighter_length_window_wins(): void
    {
        $this->template('Loose', ['ABC', ['0-9', 2, 8]]);
        $this->template('Tight', ['ABC', ['0-9', 4, 4]]);

        $result = $this->match('ABC-1234');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Tight', $result['hit']['template']->name);
    }

    public function test_fixed_text_still_beats_a_free_segment_of_the_same_shape(): void
    {
        $this->template('AllFree', [['A-Z', 3, 3], ['0-9', 4, 4]]);
        $this->template('Fixed', ['ABC', ['0-9', 4, 4]]);

        $result = $this->match('ABC-1234');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Fixed', $result['hit']['template']->name);
    }

    public function test_a_code_no_template_accepts_reports_none(): void
    {
        $this->template('Digits', ['ABC', ['0-9', 4, 4]]);

        $this->assertSame('none', $this->match('ZZ-ZZZ-9999')['status']);
    }

    public function test_genuinely_identical_templates_stay_ambiguous(): void
    {
        $this->template('One', ['ABC', ['0-9', 4, 4]]);
        $this->template('Two', ['ABC', ['0-9', 4, 4]]);

        $this->assertSame('ambiguous', $this->match('ABC-1234')['status']);
    }

    /* ---------------- case ---------------- */

    public function test_a_lowercase_code_matches_an_uppercase_template(): void
    {
        $this->template('Mixed', [['A-Z0-9', 11, 11]]);

        $result = $this->match('jd14040031z');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Mixed', $result['hit']['template']->name);
    }

    public function test_fixed_text_matches_regardless_of_case(): void
    {
        $this->template('Kit', ['KIT', ['A-Z', 3, 3], ['0-9', 4, 4]]);

        $this->assertSame('matched', $this->match('kit-wwl-0100')['status']);
        $this->assertSame('matched', $this->match('KIT-WWL-0100')['status']);
    }

    /**
     * The captured value keeps the case the user typed, so the decoded parts
     * still read back as what was in the sheet.
     */
    public function test_decoding_preserves_the_original_case(): void
    {
        $this->template('Kit', ['KIT', ['A-Z', 3, 3], ['0-9', 4, 4]]);

        $decoded = $this->match('kit-wwl-0100')['hit']['decoded'];

        $this->assertSame('kit', $decoded[0]['value']);
        $this->assertSame('wwl', $decoded[1]['value']);
    }

    /* ---------------- separators ---------------- */

    public function test_a_space_works_as_a_separator(): void
    {
        $template = $this->template('Spaced', ['NUMBER', ['0-9', 2, 2]]);
        $template->segments()->where('position', 2)->update(['separator' => ' - ']);

        $result = $this->match('NUMBER - 50');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Spaced', $result['hit']['template']->name);
    }

    public function test_a_dot_works_as_a_separator(): void
    {
        $template = $this->template('Dotted', [['0-9', 1, 1], ['0-9', 2, 2]]);
        $template->segments()->where('position', 2)->update(['separator' => '.']);

        $this->assertSame('matched', $this->match('1.02')['status']);
    }

    /**
     * A separator is punctuation. Letters and digits are segment content and
     * must never survive into one, or the pattern would eat its own value.
     */
    public function test_letters_and_digits_are_stripped_from_a_separator(): void
    {
        $this->assertSame('-', Fmt::cleanSeparator('-A1'));
        $this->assertSame(' - ', Fmt::cleanSeparator(' - '));
        $this->assertSame('.', Fmt::cleanSeparator('.'));
        $this->assertSame('', Fmt::cleanSeparator('ABC123'));
    }

    /* ---------------- ranking by fit ---------------- */

    /**
     * Fitness is weighted by length, so a template cannot win a tie merely by
     * chopping the same code into more segments.
     */
    public function test_more_segments_alone_does_not_win(): void
    {
        $this->template('Whole', ['AB', ['0-9', 6, 6]]);
        $this->template('Split', ['AB', ['0-9', 6, 6]]);

        $this->assertSame('ambiguous', $this->match('AB-123456')['status']);
    }

    /**
     * The narrowest rule the value still satisfies wins, per segment, even
     * when the looser template is otherwise identical.
     */
    public function test_the_narrowest_fitting_rule_wins_on_a_long_segment(): void
    {
        $this->template('Wide', ['DF', ['A-Z0-9', 1, 8]]);
        $this->template('Narrow', ['DF', ['0-9', 6, 6]]);

        $result = $this->match('DF-123456');

        $this->assertSame('matched', $result['status']);
        $this->assertSame('Narrow', $result['hit']['template']->name);
    }
}
