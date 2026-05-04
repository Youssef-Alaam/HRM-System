<?php

namespace App\Support;

/**
 * Position-type catalog used by the EMP-XXXXX employee-code format.
 *
 * The employee_code's first digit is the position's type_code; the
 * remaining four digits are the tenure-ordered sequence WITHIN that
 * type bucket (per the 2026-04-30 design lock with Walid). Codes are
 * sticky — once assigned they never change, even on position move.
 *
 * Bucket capacity = 9999 per type. Overflow widens to 5 tenure digits;
 * the generator throws DomainException when a bucket fills up so we
 * can spot the trigger condition explicitly rather than silently
 * truncating.
 */
final class PositionType
{
    public const EXECUTIVE = 0;

    public const ENGINEERING = 1;

    public const SALES = 2;

    public const MARKETING = 3;

    public const OPERATIONS = 4;

    public const HR = 5;

    public const FINANCE = 6;

    public const SUPPORT = 7;

    public const LEGAL = 8;

    public const OTHER = 9;

    /** @var array<int, string> */
    private const LABELS = [
        self::EXECUTIVE => 'Executive',
        self::ENGINEERING => 'Engineering / Technical',
        self::SALES => 'Sales',
        self::MARKETING => 'Marketing',
        self::OPERATIONS => 'Operations',
        self::HR => 'HR',
        self::FINANCE => 'Finance',
        self::SUPPORT => 'Customer Support',
        self::LEGAL => 'Legal',
        self::OTHER => 'Other',
    ];

    /**
     * Keyword → type_code map. First match wins; order is significant
     * because some titles match multiple buckets (e.g. "Sales Engineer"
     * is engineering, "VP of Sales" is executive).
     *
     * @var array<int, array{0: int, 1: string[]}>
     */
    private const KEYWORDS = [
        // Executive — the C-suite + president/founder titles. Listed first
        // so "Chief of Staff" and "VP Sales" land in Executive, not Sales.
        [self::EXECUTIVE, ['ceo', 'cfo', 'cto', 'coo', 'cmo', 'cio', 'chro', 'chief', 'president', 'founder', 'vp ', 'vice president']],
        [self::ENGINEERING, ['engineer', 'developer', 'devops', 'qa', 'sre', 'tech lead', 'architect', 'technical']],
        [self::HR, ['hr ', 'hr,', 'human resources', 'people ops', 'recruit', 'talent']],
        [self::FINANCE, ['finance', 'accountant', 'accounting', 'payroll', 'bookkeeper', 'controller', 'treasur']],
        [self::SALES, ['sales', 'account manager', 'account exec', 'business development']],
        [self::MARKETING, ['marketing', 'brand', 'content', 'seo', 'growth', 'pr ']],
        [self::SUPPORT, ['support', 'customer success', 'customer service', 'help desk']],
        [self::LEGAL, ['legal', 'counsel', 'compliance officer', 'paralegal']],
        [self::OPERATIONS, ['operations', 'office manager', 'coordinator', 'logistics', 'admin assistant']],
    ];

    /**
     * Best-effort inference of a position's type_code from its title.
     * Falls through to OTHER (9) when nothing matches.
     */
    public static function inferFromTitle(string $title): int
    {
        $haystack = ' '.strtolower($title).' ';

        foreach (self::KEYWORDS as [$code, $words]) {
            foreach ($words as $word) {
                if (str_contains($haystack, $word)) {
                    return $code;
                }
            }
        }

        return self::OTHER;
    }

    /**
     * Whether a value is a valid position type_code (0-9).
     */
    public static function isValid(int $code): bool
    {
        return array_key_exists($code, self::LABELS);
    }

    public static function label(int $code): string
    {
        return self::LABELS[$code] ?? self::LABELS[self::OTHER];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::LABELS;
    }
}
