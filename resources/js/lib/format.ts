/**
 * Centralized formatters for YZH HR. DESIGN.md §9 locks the user-visible
 * formats — never inline a Date.toLocaleString call in a component.
 *
 * Locked outputs:
 *   formatDate(date)     → "23 Apr 2026"
 *   formatTime(date)     → "3:00 PM"
 *   formatDateTime(date) → "23 Apr 2026 · 3:00 PM"
 *   formatMoneyEgp(p)    → "EGP 7,000.00"   (input is piasters per CLAUDE.md)
 *   formatPhoneEg(s)     → "010 1234 5678"
 *   formatNationalIdEg(s) → "2 9803 12345678"
 */

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

function asDate(input: Date | string | number): Date {
    return input instanceof Date ? input : new Date(input);
}

export function formatDate(input: Date | string | number): string {
    const d = asDate(input);
    const day = String(d.getDate()).padStart(2, '0');
    const month = MONTHS[d.getMonth()];
    const year = d.getFullYear();
    return `${day} ${month} ${year}`;
}

export function formatTime(input: Date | string | number): string {
    const d = asDate(input);
    let hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const period = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${period}`;
}

export function formatDateTime(input: Date | string | number): string {
    return `${formatDate(input)} · ${formatTime(input)}`;
}

/**
 * Egyptian Pound from piasters (1 EGP = 100 piasters per CLAUDE.md).
 * Always renders 2 fractional digits.
 */
export function formatMoneyEgp(piasters: number): string {
    const egp = piasters / 100;
    const formatted = egp.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return `EGP ${formatted}`;
}

/**
 * Egyptian mobile (11 digits, leading 0). Returns the input unchanged if it
 * doesn't match the expected length so callers see what they passed in.
 */
export function formatPhoneEg(raw: string): string {
    const digits = raw.replace(/\D/g, '');
    if (digits.length !== 11) return raw;
    return `${digits.slice(0, 3)} ${digits.slice(3, 7)} ${digits.slice(7)}`;
}

/**
 * Egyptian national ID (14 digits). Same fall-through rule as phone.
 */
export function formatNationalIdEg(raw: string): string {
    const digits = raw.replace(/\D/g, '');
    if (digits.length !== 14) return raw;
    return `${digits.slice(0, 1)} ${digits.slice(1, 5)} ${digits.slice(5)}`;
}
