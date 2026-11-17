<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terra API v1 | Developer reference</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.fonts')
    <style>
        body { background: #f6f7fb; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 32px 16px 60px; }
        h1 { color: #19265d; font-weight: 800; }
        h2 { color: #19265d; font-size: 1.15rem; margin-top: 32px; }
        .ep { background: #fff; border: 1px solid #e5e9f1; border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; display: grid; grid-template-columns: 70px minmax(0, 1fr); gap: 4px 12px; }
        .m { font: 700 12px ui-monospace, monospace; padding: 3px 0; border-radius: 6px; text-align: center; color: #fff; align-self: start; }
        .GET { background: #116a9c; } .POST { background: #147847; } .PATCH { background: #8f5a08; } .DELETE { background: #b42318; }
        code.path { font-size: .9rem; color: #19265d; }
        pre { background: #111b45; color: #e6e9f5; padding: 14px; border-radius: 10px; font-size: .82rem; }
        .ep small { grid-column: 2; color: #5f6880; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Terra API v1</h1>
    <p class="lead">JSON API for the Terra mobile app and approved integrations. Base URL: <code>{{ url('/api/v1') }}</code></p>

    <h2>Authentication</h2>
    <p>Request a token, then send it as <code>Authorization: Bearer &lt;token&gt;</code> with <code>Accept: application/json</code>.
        Tokens expire after 90 days and are tied to the user's role (tenant, staff, finance, agent or technician). Users with two-step sign-in create tokens from <em>Account → Security</em> in the web app.
        Every endpoint applies the same access rules as the web portals; requests are rate-limited.</p>
    <pre>POST /api/v1/auth/token
{ "email": "tenant@example.com", "password": "••••••", "device_name": "Pixel 8" }

201 { "token": "1|abc…", "token_type": "Bearer", "expires_at": "…", "user": { "id": 7, "role": "tenant", … } }</pre>

    @php
        $groups = [
            'Account' => [
                ['POST', '/auth/token', 'Get a token (email, password, device_name).'],
                ['DELETE', '/auth/token', 'Revoke the token used for this request.'],
                ['GET', '/me', 'Current user, role, language and unread counts.'],
                ['GET', '/notifications?unread=1', 'Notifications (paginated).'],
                ['POST', '/notifications/{id}/read', 'Mark a notification read.'],
                ['GET', '/conversations', 'Conversations with unread flag.'],
                ['GET', '/conversations/{id}/messages?after={messageId}', 'Messages (newest 100, or those after an id). Marks them read.'],
                ['POST', '/conversations/{id}/messages', 'Send a message: { body }.'],
            ],
            'Tenant (ability: tenant)' => [
                ['GET', '/tenant/dashboard', 'Lease, balance due, next invoice, open maintenance.'],
                ['GET', '/tenant/leases', 'All leases.'],
                ['GET', '/tenant/invoices?status=open', 'Invoices (paginated).'],
                ['POST', '/tenant/invoices/{id}/pay', 'Start a Mobile Money payment: { amount, phone }. Returns a payment intent.'],
                ['GET', '/tenant/payment-intents/{id}', 'Poll a payment: pending | successful | failed | expired, plus receipt_url.'],
                ['GET', '/tenant/payments', 'Payment history with receipt links.'],
                ['GET', '/tenant/maintenance-requests', 'Maintenance requests and assigned technician.'],
                ['POST', '/tenant/maintenance-requests', 'Report a problem: { lease_id, title, description, category, priority }.'],
            ],
            'Landlord / manager / accountant (ability: staff or finance)' => [
                ['GET', '/portfolio/summary', 'Portfolio dashboard: properties, units, occupancy, expected/collected/outstanding rent, expenses, net income.'],
                ['GET', '/portfolio/properties', 'Properties with coordinates and unit counts.'],
                ['GET', '/portfolio/properties/{id}', 'Buildings, units, statuses and current tenants.'],
                ['GET', '/portfolio/maintenance-requests?status=open', 'Maintenance across the portfolio.'],
                ['GET', '/portfolio/payments', 'Rent payments.'],
            ],
            'Technician (ability: technician)' => [
                ['GET', '/technician/jobs?status=open', 'Assigned jobs with location and tenant contact.'],
                ['PATCH', '/technician/jobs/{id}', 'Update a job: { status: scheduled|in_progress|on_hold|resolved, scheduled_at?, note?, actual_cost? }.'],
            ],
            'Public (no token)' => [
                ['GET', '/listings?q=&district=&available=1&bbox=south,west,north,east', 'Active properties with coordinates, availability and starting rent — for maps and marketplace integration.'],
            ],
        ];
    @endphp

    @foreach($groups as $group => $endpoints)
        <h2>{{ $group }}</h2>
        @foreach($endpoints as [$method, $path, $desc])
            <div class="ep"><span class="m {{ $method }}">{{ $method }}</span><code class="path">{{ $path }}</code><small>{{ $desc }}</small></div>
        @endforeach
    @endforeach

    <h2>Errors</h2>
    <p><code>401</code> missing/invalid token · <code>403</code> wrong role or no access · <code>404</code> not found or not yours · <code>422</code> validation errors (<code>{ message, errors }</code>) · <code>429</code> too many requests.</p>
    <p>Lists are paginated Laravel-style (<code>data</code>, <code>links</code>, <code>meta</code>; use <code>?page=2</code>). Send <code>Accept-Language: rw</code>, <code>fr</code> or <code>en</code> to choose the language of messages.</p>
</div>
</body>
</html>
