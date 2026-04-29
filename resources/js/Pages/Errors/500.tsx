import ErrorShell, { useHomeRoute } from '@/Components/ErrorShell';

export default function ServerError() {
    const home = useHomeRoute();

    return (
        <ErrorShell
            title="Something broke"
            code={500}
            label="Server error"
            heading="Something broke on our side."
            body={
                <p>
                    Try again in a few minutes. If it keeps failing, reach out
                    to your HR admin.
                </p>
            }
            primary={home}
        />
    );
}
