<!doctype html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Codelink Infotech</title>
    <link href="https://fonts.googleapis.com/css?family=Lato|Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i"
        rel="stylesheet">
</head>
<style>
    .table {
        width: 100%;
        color: #212529;
    }

    .table-bordered {
        border: 1px solid #dee2e6;
    }

    .table-bordered thead td,
    .table-bordered thead th {
        border-bottom-width: 2px;
    }

    .table thead th {
        vertical-align: bottom;
        border-right: 1px solid #2c2c2c;
    }

    .table th:nth-of-type(1) {
        border-top: 0px solid #2c2c2c;
    }

    .table th:nth-of-type(2) {
        border-top: 0px solid #2c2c2c;
    }

    .table th:nth-of-type(3) {
        border-top: 0px solid #2c2c2c;
    }

    .table th:nth-of-type(4) {
        border-top: 0px solid #2c2c2c;
    }

    .table td:nth-of-type(1) {
        border-right: 0px solid #2c2c2c;
    }

    .table td:nth-of-type(2) {
        border-right: 0px solid #2c2c2c;
    }

    .table td:nth-of-type(3) {
        border-right: 0px solid #2c2c2c;
    }

    .table td:nth-of-type(4) {
        border-right: 0px solid #2c2c2c;
    }

    .table thead th:nth-last-of-type(1) {
        border-bottom: 0px solid #2c2c2c;
        border-right: 0px solid #2c2c2c;
    }

    .table td:nth-last-of-type(1) {
        border-top: 1px solid #2c2c2c;
        border-right: 0px solid #2c2c2c;
    }

    .table td,
    .table th {
        padding: .25rem;
        vertical-align: top;
        border-top: 1px solid #2c2c2c;
        border-right: 1px solid #2c2c2c;
    }

    body,
    h1,
    h2,
    h3,
    h4,
    p,
    th,
    td,
    tr,
    p,
    table {
        font-family: "sans-serif";
    }

    .w300 {
        width: 300px;
    }

    .f20 {
        font-size: 17px;
    }

    .codework-data h2 {
        margin-bottom: 10px;
    }

    .codework-data h3 {
        margin-top: 0px;
    }

    table.hkkkkk tr td:nth-child(2) {
        width: 25%;
    }
</style>
<body>
    <table border="1" align="center" valign="center" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td class="logo-center" style="border: 0px;padding:15px 0px; width: 100%;" align="center" valign="center">
                <img src=" site_url . 'images/codelink.svg" width="200" style="padding: 0px 15px;" />
            </td>
        </tr>
        <tr>
            <td style="border: 0px; width: 100%;text-align: center; padding-bottom: 10px;display: inline-block;"
                class="codework-data">
                <h3 style="text-align: center; margin-bottom: 10px; display: inline-block;    font-size: 14px; ">(402,
                    Valentina Business Hub, LP Savani Rd, near Shell Petrol Pump, Adajan, Surat, Gujarat 395009)</h3>
            </td>
        </tr>
    </table>
    <p style="text-align:center; margin: 5px;"><b>Pay Slip For {{ $data['pay_slip_for'] }}</b></p>
    <table style="padding-bottom: 15px; border-bottom: 0px solid #2c2c2c;" border="1" align="center" valign="center"
        width="100%">
        <tr>
            <td style="  border-bottom: 2px solid #111; padding: 10px 0px; display: inline-block;    background: #f3f3f3; "
                align="center" valign="center" width="100%">
                <p style="text-align:center;"><b> {{ $data['employee_name'] }}</b></p>
            </td>
        </tr>
        <tr>
            <td style="border: 1px;" align="left" valign="left" width="100%">
                <table class="hkkkkk" style="Width: 100%;">
                    <tr>
                        <td width="200px;">Employee Number: </td>
                        <td> {{ $data['emp_id'] }} </td>
                        <td width="250px;">CTC: </td>
                        <td style="width: 25%;"> {{ $data['curr_salary'] }} </td>
                    </tr>
                    <tr>
                        <td>Designation: </td>
                        <td> {{ $data['designation'] }}</td>
                        <td width="250px;">Working Days: </td>
                        <td style="width: 25%;"> {{ $data['total_working_days'] }}</td>
                    </tr>
                    <tr>
                        <td>Bank Name: </td>
                        <td> {{ $data['bank_name'] }}</td>
                        <td width="250px;">Paid Days: </td>
                        <td style="width: 25%;">{{ $data['employees_working_days'] }} </td>
                    </tr>
                    <tr>
                        <td>Bank Account: </td>
                        <td> {{ $data['bank_account'] }}</td>
                        <td width="250px;">Week Off: </td>
                        <td style="width: 25%;">{{ $data['total_working_days'] }} </td>
                    </tr>
                    <tr>
                        <td>PAN No: </td>
                        <td> {{ $data['pan'] }}</td>
                        <td width="250px;">LWP: </td>
                        <td style="width: 25%;"> {{ $data['lwp'] }}</td>
                    </tr>
                    <tr>
                        <td>Date of Joining: </td>
                        <td> {{ $data['date_of_joining'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table style="border-top: 0px solid #2c2c2c;" border="1" align="center" valign="center" cellpadding="0"
        cellspacing="0" width="100%">
        <tr>
            <td style="border: 1px;" align="left" valign="left" width="100%">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width:200;border-top:0px;">Earnings</th>
                            <th style="border-top:0px;">Amount</th>
                            <th style="width:350;border-top:0px;">Deductions</th>
                            <th style="border-top:0px; border-right: 0px solid #2c2c2c;">Amount</th>
                        </tr>
                    </thead>
                    <tr>
                        <td>Basic</td>
                        <td style="text-align:right;">{{ $data['basic'] }}</td>
                        <td>P.F</td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;">{{ $data['pf'] }}</td>
                    </tr>
                    <tr>
                        <td>H.R.A</td>
                        <td style="text-align:right;"> {{ $data['hra'] }}</td>
                        <td>Professional Tax</td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;"> {{ $data['pt'] }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>TDS</td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;"> {{ $data['tds'] }} </td>
                    </tr>
                    <tr>
                        <td>Other Addition</td>
                        <td style="text-align:right;"> {{ $data['other_addition'] }}</td>
                        <td>Other Deduction</td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;"> {{ $data['od'] }}</td>
                    </tr>
                    <tr>
                        <td class="f20"><b>Total Earnings </b></td>
                        <td style="text-align:right;"> {{ $data['total_earnings'] }}</td>
                        <td class="f20"><b>Total Deductions</b></td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;"> {{ $data['total_deductions'] }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="f20"><b>Net Amount</b></td>
                        <td style="border-right: 0px solid #2c2c2c;text-align:right;">
                            {{ $data['net_amount_with_other_addition'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border-right: none;"><b>
                                {{ $data['net_amount_text'] }}</b></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <p>**This is computer generated document, this doesn\'t require a signature.</p>
</body>

</html>
