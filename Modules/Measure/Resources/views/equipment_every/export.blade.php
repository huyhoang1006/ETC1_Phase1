<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>
<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th colspan="7" style="border: 1px solid #000000;text-align: center;font-weight: bold;font-size: 20px;">Báo cáo số lượng thiết bị của từng năm sản xuất</th>
        </tr>
        <tr>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">STT</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Loại thiết bị</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Chủng loại</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Đơn vị quản lý</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Hãng sản xuất</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Năm sản xuất</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">Số lượng</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $key => $item )
            <tr>
                <td style="border: 1px solid #000000;text-align: left;">{{ $key + 1 }}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['class.type']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['zCI_Device_Type.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['zrefnr_dvql.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{!!$item['zManufacturer.zsym']??''!!}</td>
                <td style="border: 1px solid #000000;text-align: left;width: 30px">{{$item['zYear_of_Manafacture.zsym']??''}}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{$item['count']??''}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
