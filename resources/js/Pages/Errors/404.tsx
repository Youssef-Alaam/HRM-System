import ErrorShell, { useHomeRoute } from '@/Components/ErrorShell';

export default function NotFound() {
    const home = useHomeRoute();

    return (
        <ErrorShell
            title="Page not found"
            code={404}
            label="Page not found"
            heading="This page doesn't exist."
            body={
                <p>
                    The link may be broken, or the page hasn't been built yet.
                    Check the URL, or head back to a known starting point.
                </p>
            }
            primary={home}
        />
    );
}
