import ErrorShell, { useHomeRoute } from '@/Components/ErrorShell';

export default function TooManyRequests() {
    const home = useHomeRoute();

    return (
        <ErrorShell
            title="Too many requests"
            code={429}
            label="Too many requests"
            heading="That's a lot of requests."
            body={
                <p>
                    You've hit our rate limit for now. Wait a moment, then try
                    again. If this keeps happening, contact your HR admin.
                </p>
            }
            primary={home}
        />
    );
}
