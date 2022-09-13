<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>
<?php
    $maxReport = 0;
    foreach ($devices as $item) {
        if (!empty($item['report']) && count($item['report']) > $maxReport) {
            $maxReport = count($item['report']);
        }
    }
    $titleDate = 'Hiển thị toàn bộ thời gian';
    if( !empty($request['from']) && !empty($request['to'])){
        $titleDate = 'Từ ngày '. date('d/m/Y', strtotime($request['from'])) . ' đến '.date('d/m/Y', strtotime($request['to']));
    }else if( !empty($request['from']) ){
        $titleDate = 'Từ ngày '. date('d/m/Y', strtotime($request['from'])) . ' đến hiện tại';
    }else if( !empty($request['to']) ){
        $titleDate = 'Đến ngày '. date('d/m/Y', strtotime($request['to']));
    }
?>
<table class="table table-striped">
    <tbody class="thead-dark">
        <tr>
            <td valign="center" colspan="{{ 14 + $maxReport * 4 }}" style="text-align: center;font-weight: bold;font-size: 20px" rowspan="2">
                BÁO CÁO THỐNG KÊ CÔNG TÁC THÍ NGHIỆM
            </td>
        </tr>
        <tr></tr>
        <tr>
            <td valign="center" colspan="{{ 14 + $maxReport * 4 }}" style="text-align: center;font-weight: bold;" rowspan="2">
                Thời gian xuất báo cáo: {{ $titleDate }}
            </td>
        </tr>
    </tbody>
</table>
<table class="table table-striped">
    <tbody class="thead-dark">
        <tr>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Khu vực</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Trạm/Nhà máy</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Ngăn lộ/Hệ thống</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Thiết bị</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Hãng sản xuất</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Chủng loại</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Kiểu</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Số chế tạo</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Năm sản xuất</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Nước sản xuất</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Firmware/OS</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Phần mềm</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Phiên bản phần mềm</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Địa chỉ mạng</td>
            <td rowspan="2" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Thời gian lắp đặt</td>
            @if (!empty($maxReport))
                @for ($i = 1; $i <= $maxReport; $i++)
                    <td colspan="4" valign="center" style="text-align: center;font-weight: bold;width: 20px;">Thông tin thí nghiệm lần {{ $i }}</td>
                @endfor
            @endif
        </tr>
        @if (!empty($maxReport))
            <tr>
                @for ($i = 1; $i <= $maxReport; $i++)
                    <td valign="center" style="text-align: center;font-weight: bold;width: 20px;">Thời gian</td>
                    <td valign="center" style="text-align: center;font-weight: bold;width: 20px;">Người thí nghiệm {{ $i }}</td>
                    <td valign="center" style="text-align: center;font-weight: bold;width: 20px;">Nội dung thí nghiệm {{ $i }}</td>
                    <td valign="center" style="text-align: center;font-weight: bold;width: 20px;">Note {{ $i }}</td>
                @endfor
            </tr>
        @endif
    </tbody>
</table>
<table class="table table-striped">
    <tbody>
        @foreach($devices as $item)
            <tr>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zArea.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zrefnr_td.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zrefnr_nl.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">
                    <a href="{{ route('admin.device.detail', [@$item['handle_id']]) }}">{{ @$item['name'] }}</a>
                </td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zManufacturer.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zCI_Device_Type.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zCI_Device_Kind.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['serial_number'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ @$item['zCountry.name'] }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ !empty($item['info']['zhedieuhanh.zsym']) ? $item['info']['zhedieuhanh.zsym'] : '' }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ !empty($item['info']['zSoftware.zsym']) ? $item['info']['zSoftware.zsym'] : '' }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ !empty($item['info']['zversion']) ? $item['info']['zversion'] : '' }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ !empty($item['info']['zIP']) ? $item['info']['zIP'] : '' }}</td>
                <td valign="center" style="text-align: left;height: 30px">{{ !empty($item['info']['zTGLD']) ? date('d/m/Y', $item['info']['zTGLD']) : '' }}</td>
                @if (!empty($item['report']))
                    @foreach ($item['report'] as $key => $report)
                        <td valign="center" style="text-align: left;height: 30px">{{ date('d/m/Y H:i:s', @$report['creation_date']) }}</td>
                        <td valign="center" style="text-align: left;height: 30px">{{ implode(' ', [@$report['zExperimenter.last_name'], @$report['zExperimenter.middle_name'], @$report['zExperimenter.first_name'], ]) }}</td>
                        <td valign="center" style="text-align: left;height: 30px">{{ @$report['zWork_Content'] }}</td>
                        <td valign="center" style="text-align: left;height: 30px">{{ @$report['zNotes'] }}</td>
                    @endforeach
                @elseif (!empty($maxReport))
                    @for ($i = 1; $i <= $maxReport; $i++)
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    @endfor
                @endif
            </tr>
        @endforeach
        @if(!count($devices))
            <tr>
                <td colspan="11" class="text-center">Không tìm thấy bản ghi nào</td>
            </tr>
        @endif
    </tbody>
</table>
