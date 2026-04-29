import ErrorShell from '@/Components/ErrorShell';

type Props = {
    retryAfter?: number;
};

function humanizeRetryAfter(seconds: number): string | null {
    if (seconds <= 0) return null;
    if (seconds < 60) return `Back in about ${seconds} seconds.`;
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) {
        return minutes === 1
            ? 'Back in about a minute.'
            : `Back in about ${minutes} minutes.`;
    }
    const hours = Math.round(minutes / 60);
    return hours === 1
        ? 'Back in about an hour.'
        : `Back in about ${hours} hours.`;
}

export default function Maintenance({ retryAfter }: Props) {
    const eta =
        typeof retryAfter === 'number' ? humanizeRetryAfter(retryAfter) : null;

    return (
        <ErrorShell
            title="Maintenance"
            code={503}
            label="Maintenance"
            heading="YZH HR is briefly down for updates."
            body={
                <p>
                    {eta ?? 'Back shortly.'} Your data is safe. Try signing
                    back in once we're online.
                </p>
            }
        />
    );
}
