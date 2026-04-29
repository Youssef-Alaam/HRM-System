import ErrorShell, { useHomeRoute } from '@/Components/ErrorShell';

export default function Forbidden() {
    const home = useHomeRoute();

    return (
        <ErrorShell
            title="Access denied"
            code={403}
            label="Access denied"
            heading="You don't have access to this."
            body={
                <p>
                    Your role doesn't include this page. If you think it should,
                    contact your HR admin and they can adjust your permissions.
                </p>
            }
            primary={home}
        />
    );
}
