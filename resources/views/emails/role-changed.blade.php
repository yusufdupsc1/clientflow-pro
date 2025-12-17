<div>
    <p>Hi there,</p>
    <p>Your role for <strong>{{ $organization->name }}</strong> has been updated to <strong>{{ ucfirst($role) }}</strong>.</p>
    <p>If you did not expect this change, please contact your administrator.</p>
    <p>Thanks,<br>{{ config('app.name') }}</p>
</div>
