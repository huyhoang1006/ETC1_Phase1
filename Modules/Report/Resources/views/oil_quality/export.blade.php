<table class="table table-striped" style="text-align: center">
    <thead class="thead-dark" align="center">
    <tr>
        <th style="font-weight: 700;text-align: center;">STT</th>
        <th style="font-weight: 700;text-align: center;" colspan="2">Tên thiết bị</th>
        <th style="font-weight: 700;text-align: center;">Trạm</th>
        <th style="font-weight: 700;text-align: center;">Tên dầu</th>
        <th style="font-weight: 700;text-align: center;">Ngày lấy mẫu</th>
        <th style="font-weight: 700;text-align: center;">Trị số a xít (mg KOH/g)</th>
        <th style="font-weight: 700;text-align: center;">Nhiệt độ chớp cháy (°C)</th>
        <th style="font-weight: 700;text-align: center;">Màu sắc dầu</th>
        <th style="font-weight: 700;text-align: center;">Độ cách điện (kV)</th>
        <th style="font-weight: 700;text-align: center;">Tổn hao điện môi (%)</th>
        <th style="font-weight: 700;text-align: center;">Hàm lượng ẩm (ppm)</th>
        <th style="font-weight: 700;text-align: center;">Sức căng bề mặt (mN/m)</th>
        <th style="font-weight: 700;text-align: center;">Điểm</th>
    </tr>
    </thead>
    <tbody align="center">
    <?php
    $count = 1;
    ?>
    @foreach ($dataArr as $key => $item)
        <tr>
            <td style="width: 10px;text-align: center;">{{ $count }}</td>
            <td style="width: 50px;text-align: center;">{{$item['zCI_Device.name']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['year']??''}}</td>
            <td style="width: 50px;text-align: center;">{{$item['zrefnr_td.zsym']??''}}</td>
            <td style="width: 20px;text-align: center;">Đang chờ KH</td>
            <td style="width: 20px;text-align: center;">{{$item['zsamplingDate_sync']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zAcid_Number_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zFlash_Point_PMCC_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zColour_Appear_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zBreakdown_Voltage_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zDielectric_Dissip_Fact_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zWater_Content_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">{{$item['zInterfacial_Tension_Result']??''}}</td>
            <td style="width: 20px;text-align: center;">Đang chờ KH</td>
        </tr>
        <?php
        $count++;
        ?>
    @endforeach
    @if(!count($dataArr))
        <tr>
            <td colspan="14" class="text-center">Không tìm thấy bản ghi nào</td>
        </tr>
    @endif
    </tbody>
</table>
