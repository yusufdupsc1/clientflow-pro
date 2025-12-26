<<<<<<< HEAD
<div>
    <p>Hi there,</p>
    <p>Your role for <strong>{{ $organization->name }}</strong> has been updated to <strong>{{ ucfirst($role) }}</strong>.</p>
    <p>If you did not expect this change, please contact your administrator.</p>
    <p>Thanks,<br>{{ config('app.name') }}</p>
</div>
=======
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Role Updated</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .role-change {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }

        .role-box {
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
        }

        .role-old {
            background: #f3f4f6;
            color: #6b7280;
        }

        .role-new {
            background: #eff6ff;
            color: #1d4ed8;
            border: 2px solid #3b82f6;
        }

        .role-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
        }

        .role-name {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            text-transform: capitalize;
        }

        .arrow {
            color: #9ca3af;
            font-size: 24px;
        }

        .btn {
            display: inline-block;
            background: #4f46e5;
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>🔄 Role Updated</h1>
            </div>
            <div class="content">
                <p>Hello {{ $user->name }},</p>

                <p>Your role in <strong>{{ $organization->name }}</strong> has been updated:</p>

                <div class="role-change">
                    <div class="role-box role-old">
                        <div class="role-label">Previous Role</div>
                        <div class="role-name">{{ $oldRole }}</div>
                    </div>
                    <div class="arrow">→</div>
                    <div class="role-box role-new">
                        <div class="role-label">New Role</div>
                        <div class="role-name">{{ $newRole }}</div>
                    </div>
                </div>

                @if($newRole === 'owner')
                    <p>🎉 Congratulations! You now have full control over this organization.</p>
                @elseif($newRole === 'admin')
                    <p>You can now manage clients, projects, invoices, and team members.</p>
                @else
                    <p>You have view-only access to the organization's data.</p>
                @endif

                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
                </div>

                <p style="margin-top: 30px;">
                    If you have questions about this change, please contact the organization owner.
                </p>
            </div>
        </div>
        <div class="footer">
            {{ $organization->name }} • {{ config('app.name') }}
        </div>
    </div>
</body>

</html>
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
