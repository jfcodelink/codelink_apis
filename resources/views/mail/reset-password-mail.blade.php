<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Your Password</h1>
    <p>Hello {{ $userName }},</p>
    <p>Please click on the following link to reset your password:</p>
    <a href="{{ $passwordResetLink }}">Reset Password</a>
</body>
</html>
