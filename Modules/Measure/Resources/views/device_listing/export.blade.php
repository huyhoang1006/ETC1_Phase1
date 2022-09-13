<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>

<table class="table table-striped">
    <thead class="thead-blue">
        @if (empty($request['type_id']) || $request['type_id'] == 1004903)
            <tr>
                <th colspan="37" style="border: 1px solid #000000;text-align: center;font-weight: bold;font-size: 20px;">Báo cáo thống kê danh sách thiết bị</th>
            </tr>
        @elseif ($request['type_id'] == 1002783)
            <tr>
                <th colspan="20" style="border: 1px solid #000000;text-align: center;font-weight: bold;font-size: 20px;">Báo cáo thống kê danh sách thiết bị</th>
            </tr>
        @else
            <tr>
                <th colspan="20" style="border: 1px solid #000000;text-align: center;font-weight: bold;font-size: 20px;">Báo cáo thống kê danh sách thiết bị</th>
            </tr>
        @endif
        <tr>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;">STT</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Đơn vị quản lý (Customer)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Khu vực (Tỉnh/Thành phố) (Area)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 40px;">Trạm/Nhà máy (Substation/Plant)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 40px;">Ngăn lộ/Hệ thống/Tổ máy (Bay/System/Unit)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Thiết bị (Equipment)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Chủng loại (Type)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hãng sản xuất (Manufacturer)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Kiểu thiết bị(Model)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số chế tạo (Serial Number)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Năm sản xuất (Year of Manufacture)</th>
            <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Nước sản xuất (Country/Origin)</th>
            @if (empty($request['type_id']) || $request['type_id'] == 1004903)
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Đơn vị quản lý điểm đo</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Đơn vị giao điện năng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Đơn vị nhận điện năng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Vị trí địa lý</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Loại điểm đo</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Ngày nghiệm thu tĩnh</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Ngày nghiệm thu mang tải</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Thông tin TU sử dụng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Thông tin TI sử dụng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hàng kẹp mạch dòng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hàng kẹp mạch áp</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Điện áp (voltage)</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Dòng điện (current)</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Cấp chính xác P</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Cấp chính xác Q</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hằng số xung</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Tỷ số TU cài đặt trong công tơ</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Tỷ số TI cài đặt trong công tơ</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số lần lập trình</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Thời gian lập trình lần cuối</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Kết quả kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Cảnh báo</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hạn kiểm định (Valid until)</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số tem kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số seri tem kiểm định</th>
            @elseif ($request['type_id'] == 1002783)
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hạn kiểm định (Valid until)</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số tem kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số seri tem kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Kết quả kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Tỷ số biến dòng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Cấp chính xác</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Dung lượng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Niêm phong nắp boóc</th>
            @else
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Hạn kiểm định (Valid until)</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số tem kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Số seri tem kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Kết quả kiểm định</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Tỷ số biến áp</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Cấp chính xác</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Dung lượng</th>
                <th style="border: 1px solid #000000;font-weight: bold;text-align: center;width: 30px;">Giá trị tụ</th>
            @endif
        </tr>
    </thead>
    <tbody>
        <?php
            $count = 1;
        ?>
        @foreach ($deviceArr as $device)
            @if (!empty($request['zdvquanlydiemdosrel']) && (empty($device['info']['zdvquanlydiemdosrel.zsym']) || strpos($device['info']['zdvquanlydiemdosrel.zsym'], $request['zdvquanlydiemdosrel']) === false))
                @continue
            @endif
            @if (!empty($request['zdvgiaodiennangsrel']) && (empty($device['info']['zdvgiaodiennangsrel.zsym']) || strpos($device['info']['zdvgiaodiennangsrel.zsym'], $request['zdvgiaodiennangsrel']) === false))
                @continue
            @endif
            @if (!empty($request['zdvnhandiennangsrel']) && (empty($device['info']['zdvnhandiennangsrel.zsym']) || strpos($device['info']['zdvnhandiennangsrel.zsym'], $request['zdvnhandiennangsrel']) === false))
                @continue
            @endif
            @if (!empty($request['zloaidiemdosrel']) && (empty($device['info']['zloaidiemdosrel.zsym']) || strpos($device['info']['zloaidiemdosrel.zsym'], $request['zloaidiemdosrel']) === false))
                @continue
            @endif
            <tr>
                <td style="border: 1px solid #000000;text-align: left;">{{ $count }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zrefnr_dvql.zsym']) ? $device['zrefnr_dvql.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zArea.zsym']) ? $device['zArea.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zrefnr_td.zsym']) ? $device['zrefnr_td.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zrefnr_nl.zsym']) ? $device['zrefnr_nl.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['name']) ? $device['name'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zCI_Device_Type.zsym']) ? $device['zCI_Device_Type.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{!! !empty($device['zManufacturer.zsym']) ? $device['zManufacturer.zsym'] : '' !!}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zCI_Device_Kind.zsym']) ? $device['zCI_Device_Kind.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['serial_number']) ? $device['serial_number'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zYear_of_Manafacture.zsym']) ? $device['zYear_of_Manafacture.zsym'] : '' }}</td>
                <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['zCountry.name']) ? $device['zCountry.name'] : '' }}</td>
                @if (empty($request['type_id']) || $request['type_id'] == 1004903)
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdvquanlydiemdosrel.zsym']) ? $device['info']['zdvquanlydiemdosrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdvgiaodiennangsrel.zsym']) ? $device['info']['zdvgiaodiennangsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdvnhandiennangsrel.zsym']) ? $device['info']['zdvnhandiennangsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zvitridialy']) ? $device['info']['zvitridialy'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zloaidiemdosrel.zsym']) ? $device['info']['zloaidiemdosrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zngaynttinhdate']) ? date('d/m/Y', $device['info']['zngaynttinhdate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zngayntmangtaidate']) ? date('d/m/Y', $device['info']['zngayntmangtaidate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zTUinform']) ? $device['info']['zTUinform'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zTIinform']) ? $device['info']['zTIinform'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zhangkepmachdong']) ? $device['info']['zhangkepmachdong'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zhangkepmachap']) ? $device['info']['zhangkepmachap'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{!! !empty($device['info']['zdienapsrel.zsym']) ? $device['info']['zdienapsrel.zsym'] : '' !!}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdongdiensrel.zsym']) ? $device['info']['zdongdiensrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zcapcxpsrel.zsym']) ? $device['info']['zcapcxpsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zcapcxqsrel.zsym']) ? $device['info']['zcapcxqsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{!! !empty($device['info']['zhangsosxungsrel.zsym']) ? $device['info']['zhangsosxungsrel.zsym'] : '' !!}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztysotu']) ? $device['info']['ztysotu'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztysoti']) ? $device['info']['ztysoti'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zsolanlaptrinh']) ? $device['info']['zsolanlaptrinh'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zlasttimelaptrinhdate']) ? date('d/m/Y', $device['info']['zlasttimelaptrinhdate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zcanhbao']) ? $device['info']['zcanhbao'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zsotemkiemdinh']) ? $device['info']['zsotemkiemdinh'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                @elseif ($request['type_id'] == 1002783)
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztemkiemdinhnum']) ? $device['info']['ztemkiemdinhnum'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztysobiendongstr']) ? $device['info']['ztysobiendongstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zcapchinhxacstr']) ? $device['info']['zcapchinhxacstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdungluonstr']) ? $device['info']['zdungluonstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zniemphongnapbocstr']) ? $device['info']['zniemphongnapbocstr'] : '' }}</td>
                @else
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztemkiemdinhnum']) ? $device['info']['ztemkiemdinhnum'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['ztysobienapstr']) ? $device['info']['ztysobienapstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zcapchinhxacstr']) ? $device['info']['zcapchinhxacstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zdungluonstr']) ? $device['info']['zdungluonstr'] : '' }}</td>
                    <td style="border: 1px solid #000000;text-align: left;">{{ !empty($device['info']['zgiatritustr']) ? $device['info']['zgiatritustr'] : '' }}</td>
                @endif
            </tr>
            <?php
                $count++;
            ?>
        @endforeach
    </tbody>
</table>
