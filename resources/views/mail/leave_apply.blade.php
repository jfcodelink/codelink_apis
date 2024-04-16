@extends('mail.index')
@section('content')
@php
    if ($data['leave_type'] == 1) {
        $leave_type1 = 'Full day';
    } elseif ($data['leave_type'] == 2) {
        $leave_type1 = 'Half day';
    } else {
        $leave_type1 = 'N/A';
    }
@endphp

<tr>
    <td align="center" valign="center" style="text-align:center; padding-bottom: 10px">
        <div style="margin-bottom:55px; text-align:left">
            <div
                style="font-size:14px; text-align:left; font-weight:500; margin:0 60px 33px 60px; font-family:Arial,Helvetica,sans-serif">
                <h1 style='font-size:20px;margin:0 0 20px 0;font-family:Arial,sans-serif; text-align: center;'>Leave
                    aplication by <span style="color:#181C32; font-weight:600;"><?php echo ucfirst(strtolower($data['user_name'])); ?></span>
                </h1>
                <p style="color:#3F4254; line-height:1.6">
                    Below are the leave details of <?php echo ucfirst(strtolower($data['user_name'])); ?> employee:
                </p>
                <p style="color:#3F4254; line-height:1.6">Employee name: <span
                        style="color:#181C32; font-weight:600;"><?php echo ucfirst(strtolower($data['user_name'])); ?></span></p>
                <p style="color:#3F4254; line-height:1.6">Employee email: <span style="color:#181C32; font-weight:600;">
                        <?php echo $data['user_email']; ?></span></p>
                <p style="color:#3F4254; line-height:1.6">Leave type: <span
                        style="color:#181C32; font-weight:600;"><?php echo $leave_type1; ?></span></p>
                <p style="color:#3F4254; line-height:1.6">Leave apply date: <span
                        style="color:#181C32; font-weight:600;"><?php echo $data['leave_post_date']; ?></span></p>
                <p style="color:#3F4254; line-height:1.6">Leave message: <span
                        style="color:#181C32; font-weight:600;"><?php echo $data['message']; ?></span></p>
            </div>
        </div>
    </td>
</tr>
@endsection
