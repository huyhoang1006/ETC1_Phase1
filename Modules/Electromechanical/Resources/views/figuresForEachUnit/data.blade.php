<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th rowspan="2">STT</th>
            <th rowspan="2">Đơn vị sử dụng</th>
            <th rowspan="2">Thời gian</th>
            <th rowspan="2">Hãng sản xuất</th>
            <th colspan="2">Dây dẫn trần</th>
            <th colspan="2">Cáp vặn xoắn</th>
            <th colspan="2">Cáp bọc hạ áp</th>
            <th colspan="2">Cáp bọc trung áp</th>
            <th colspan="2">Cáp lực trung thế</th>
        </tr>
        <tr>
            <th>Số mẫu</th>
            <th>Tổng số lô (cuộn)</th>
            <th>Số mẫu</th>
            <th>Tổng số lô (cuộn)</th>
            <th>Số mẫu</th>
            <th>Tổng số lô (cuộn)</th>
            <th>Số mẫu</th>
            <th>Tổng số lô (cuộn)</th>
            <th>Số mẫu</th>
            <th>Tổng số lô (cuộn)</th>
        </tr>
    </thead>
    <tbody align="center">
        <?php
            $count = 1;
        ?>
        @foreach ($dataArr as $key => $val)
            <?php
                $rowSpan = 1;
            ?>
            @foreach ($val as $index => $item)
                <tr>
                    <td>{{ $count }}</td>
                    @if (count($val) > 1)
                        @if ($rowSpan == 1)
                            <td rowspan="{{ count($val) }}">{{ $key }}</td>
                        @endif
                    @else
                        <td>{{ $key }}</td>
                    @endif
                    <td>{{ explode('__', $index)[0] }}</td>
                    <td>{{ explode('__', $index)[1] }}</td>
                    <td>{{ $item[config('constant.electromechanical.device.type.day_dan_tran')] }}</td>
                    <td></td>
                    <td>{{ $item[config('constant.electromechanical.device.type.cap_van_xoan')] }}</td>
                    <td></td>
                    <td>{{ $item[config('constant.electromechanical.device.type.cap_boc_ha_ap')] }}</td>
                    <td></td>
                    <td>{{ $item[config('constant.electromechanical.device.type.cap_boc_trung_ap')] }}</td>
                    <td></td>
                    <td>{{ $item[config('constant.electromechanical.device.type.cap_luc_trung_the')] }}</td>
                    <td></td>
                </tr>
                <?php
                    $count++;
                    $rowSpan++;
                ?>
            @endforeach
        @endforeach
        @if(!count($dataArr))
            <tr>
                <td colspan="14" class="text-center">Không tìm thấy bản ghi nào</td>
            </tr>
        @endif
    </tbody>
</table>
