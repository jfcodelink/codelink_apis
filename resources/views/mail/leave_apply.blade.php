<!-- resources/views/emails/leave_apply.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <title>Leave Application</title>
</head>

<body>
    <h1>Leave Application</h1>
    <p><strong>User Name:</strong> {{ $data['user_name'] }}</p>
    <p><strong>User Email:</strong> {{ $data['user_email'] }}</p>
    <p><strong>Leave Type:</strong> {{ $data['leave_type'] }}</p>
    <p><strong>Leave From:</strong> {{ $data['leave_from'] }}</p>
    <p><strong>Leave To:</strong> {{ $data['leave_to'] }}</p>
    <p><strong>Message:</strong> {{ $data['message'] }}</p>
    <p><strong>Leave Post Date:</strong> {{ $data['leave_post_date'] }}</p>
</body>

</html>
