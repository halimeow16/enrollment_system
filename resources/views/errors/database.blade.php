<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Unavailable</title>

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            color: #111827;
            background: #ffffff;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .content {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .code {
            border-right: 1px solid #d1d5db;
            padding-right: 24px;
            font-size: 24px;
            font-weight: 500;
            letter-spacing: 0.08em;
        }

        .message {
            font-size: 18px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hint {
            margin-top: 14px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            letter-spacing: 0;
            text-transform: none;
        }

        .actions {
            margin-top: 18px;
        }

        .button {
            border: 0;
            background: transparent;
            color: #4b5563;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        @media (max-width: 640px) {
            .content {
                align-items: flex-start;
                gap: 18px;
            }

            .code {
                padding-right: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="content">
            <div class="code">503</div>
            <div>
                <div class="message">Database Unavailable</div>
                <div class="hint">
                    The system cannot connect to MySQL. Please start the database service, then try again.
                </div>
                <div class="actions">
                    <button type="button" class="button" onclick="window.location.reload()">Refresh</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
