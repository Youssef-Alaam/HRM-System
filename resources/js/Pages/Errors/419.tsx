import ErrorShell from '@/Components/ErrorShell';

export default function PageExpired() {
    return (
        <ErrorShell
            title="Session expired"
            code={419}
            label="Session expired"
            heading="Your session expired."
            body={
                <p>
                    For your safety, you've been signed out after a period of
                    inactivity. Sign in again to pick up where you left off.
                </p>
            }
            primary={{ href: route('login'), label: 'Sign in again' }}
        />
    );
}
