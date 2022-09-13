<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>
@if (!empty($error))
    <table class="table table-striped">
        <thead class="thead-dark" align="center">
        @foreach ($error as $val)
            <tr>
                <th>{{ $val }}</th>
            </tr>
        @endforeach
        </thead>
    </table>
@else
    @include('report::numberOfDevicesByManufactureReport.data')
@endif
