<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Market Jango admin account</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">

<h2>Hello {{ $name }},</h2>

<p>You have been added as an admin/manager on <strong>Market Jango</strong>.</p>

<p>Here are your login details:</p>

<ul>
    <li><strong>Login email:</strong> {{ $email }}</li>
    <li><strong>Temporary password:</strong> {{ $tempPassword }}</li>
</ul>

<p>
    Please log in using this temporary password and change it immediately after your first login.
</p>

@if(!empty($loginUrl))
    <p>
        You can log in here:
        <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
    </p>
@endif

<p>If you did not expect this email, please contact the system administrator.</p>

<p>Regards,<br>Market Jango Team</p>

</body>
</html>

