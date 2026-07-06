<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>
    <p>You requested to reset your password. Click the link below to reset your password:</p>
<a href="{{ $resetLink }}">click here</a>
<p>If you did not request a password reset, you can ignore this email.</p>
<p>Dont Reply To this mail.</p>


</body>
</html>
