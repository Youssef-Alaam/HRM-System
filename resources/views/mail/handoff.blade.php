{{--
    Handoff email body — rendered when `php artisan handoff:send` fires.
    Designed for phone reading at the gym: short paragraphs, monospace for
    git/test data, no images that need to download. Inline CSS so Gmail's
    image proxy doesn't strip styles.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $digest['subject'] }}</title>
</head>
<body style="margin:0;padding:0;background:#eff0f1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#121212;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eff0f1;padding:24px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #dee1e7;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#121212;padding:16px 20px;">
                            <p style="margin:0;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.24em;text-transform:uppercase;color:#d0a946;">
                                {{ $digest['date'] }} / Build digest
                            </p>
                            <h1 style="margin:6px 0 0;font-size:20px;font-weight:600;color:#eff0f1;letter-spacing:-0.01em;">
                                YZH HR.
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 20px;">
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.55;color:#121212;">
                                Hello, {{ $digest['recipient_name'] }}.
                            </p>

                            <p style="margin:0 0 4px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#7a7a7a;">
                                Phase
                            </p>
                            <p style="margin:0 0 14px;font-size:14px;color:#121212;">
                                {{ $digest['phase'] }}
                            </p>

                            <p style="margin:0 0 4px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#7a7a7a;">
                                Current task
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;color:#121212;line-height:1.5;">
                                {{ $digest['current_task'] }}
                            </p>

                            <hr style="border:none;border-top:1px solid #dee1e7;margin:0 0 18px;">

                            <p style="margin:0 0 10px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#d0a946;">
                                Recent commits ({{ $digest['since'] }})
                            </p>

                            @if (count($digest['commits']) === 0)
                                <p style="margin:0 0 18px;font-size:13px;color:#54595f;">
                                    No commits in this window.
                                </p>
                            @else
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
                                    @foreach ($digest['commits'] as $c)
                                        <tr>
                                            <td style="padding:6px 0;border-bottom:1px solid #dee1e7;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#121212;line-height:1.45;vertical-align:top;">
                                                <span style="color:#a3852f;">{{ $c['hash'] }}</span>
                                                <span style="color:#7a7a7a;">&nbsp;{{ $c['date'] }}</span>
                                                <br>
                                                <span style="color:#121212;">{{ $c['subject'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            <p style="margin:0 0 4px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#7a7a7a;">
                                Tests
                            </p>
                            <p style="margin:0 0 18px;font-size:13px;color:#121212;font-family:ui-monospace,Menlo,Consolas,monospace;">
                                {{ $digest['tests'] }}
                            </p>

                            <p style="margin:0 0 4px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#7a7a7a;">
                                Branch
                            </p>
                            <p style="margin:0 0 6px;font-size:13px;color:#121212;font-family:ui-monospace,Menlo,Consolas,monospace;">
                                {{ $digest['branch'] }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#eff0f1;padding:14px 20px;text-align:center;">
                            <p style="margin:0;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:#7a7a7a;">
                                YZH Solutions / Cairo / Internal use only
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
