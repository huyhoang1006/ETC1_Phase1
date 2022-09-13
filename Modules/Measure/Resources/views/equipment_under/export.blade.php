<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>
<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th colspan="7" style="border: 1px solid #000000;text-align: center;font-weight: bold;font-size: 20px;">Báo cáo danh sách thiết bị theo hạn kiểm định</th>
        </tr>
        <tr>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">STT</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Trạm/ nhà máy</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Ngăn lộ</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Thiết bị</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Chủng loại</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Hạn kiểm định</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Vị trí địa lý</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $key => $item )
            <tr>
                <td style="border: 1px solid #000000;text-align: left;">{{ $key + 1 }}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 40px">{{$item['zrefnr_td.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 20px">{{$item['zrefnr_nl.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 40px">{{$item['name']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['zCI_Device_Type.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{ @$item['zhankiemdinhdate'] ? date('d-m-Y', @$item['zhankiemdinhdate']) : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['zvitridialy']??''}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
