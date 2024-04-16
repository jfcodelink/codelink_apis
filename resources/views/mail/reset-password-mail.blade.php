@extends('mail.index')
@section('content')
    <tr>
        <td align="center" valign="center" style="text-align:center; padding-bottom: 10px">
            <div style="margin-bottom:55px; text-align:left">
                <div
                    style="font-size:14px; text-align:left; font-weight:500; margin:0 60px 33px 60px; font-family:Arial,Helvetica,sans-serif">
                    <h1 style='font-size:20px;margin:0 0 20px 0;font-family:Arial,sans-serif; text-align: center;'>Reset your
                        password</h1>
                    <p style="color:#181C32; font-weight:600; margin-bottom:27px">Hello <?php echo ucfirst(strtolower($userName)); ?>,</p>
                    <p style="color:#3F4254; line-height:1.6">We hope this message finds you well, We've received a request
                        to reset your password. If you made this request, please click on the link below to create a new
                        password:</p>
                    <div style="text-align: center; margin:15px 0; ">
                        <a href="<?php echo $passwordResetLink; ?>" target="_blank"
                            style="background-color:#f4511e; text-decoration: none; border-radius:6px; display:inline-block; padding:11px 19px; color: #FFFFFF; text-decoration: none; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif">Change
                            Password</a>
                    </div>
                    <p style="color:#3F4254; line-height:1.6">If you did not request a password reset, please disregard this
                        email. Your password will remain unchanged.</p>
                </div>
            </div>
        </td>
    </tr>
@endsection
