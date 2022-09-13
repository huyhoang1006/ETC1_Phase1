<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th style="font-weight: 700;text-align: center;">STT</th>
            <th style="font-weight: 700;text-align: center;">Đơn vị sử dụng</th>
            <th style="font-weight: 700;text-align: center;">Trạm/ Nhà máy</th>
            <th style="font-weight: 700;text-align: center;">Thiết bị</th>
            <th style="font-weight: 700;text-align: center;">Kiểu thí nghiệm</th>
            <th style="font-weight: 700;text-align: center;">Kiểu thiết bị</th>
            <th style="font-weight: 700;text-align: center;">Hãng sản xuất</th>
            <th style="font-weight: 700;text-align: center;">Người thí nghiệm</th>
            <th style="font-weight: 700;text-align: center;">Ngày thí nghiệm</th>
        </tr>
    </thead>
    <tbody align="center">
        <?php
            $count = 1;
        ?>
        @foreach ($dataArr as $key => $item)
            <tr>
                <td>{{ $count }}</td>
                <td style="width: 30px">{{$item['zrefnr_dvql.zsym']??''}}</td>
                <td style="width: 30px">{{$item['zrefnr_td.zsym']??''}}</td>
                <td style="width: 30px">{{$item['name']??''}}</td>
                <td style="width: 30px">{{$item['zCI_Device.zStage.zsym']??''}}</td>
                <td style="width: 30px">{{$item['zCI_Device_Kind.zsym']??''}}</td>
                <td style="width: 30px">{{$item['zManufacturer.zsym']??''}}</td>
                <td style="width: 30px">
                    @if(!empty($item['zExperimenter.dept']) && $item['zExperimenter.dept'] == 1000001)
                        {{$item['zExperimenter.combo_name']??''}}<br>
                    @endif
                    @if(!empty($item['zExperimenter1.dept']) && $item['zExperimenter1.dept'] == 1000001)
                        {{$item['zExperimenter1.combo_name']??''}}<br>
                    @endif
                    @if(!empty($item['zExperimenter2.dept']) && $item['zExperimenter2.dept'] == 1000001)
                        {{$item['zExperimenter2.combo_name']??''}}<br>
                    @endif
                    @if(!empty($item['zExperimenter3.dept']) && $item['zExperimenter3.dept'] == 1000001)
                        {{$item['zExperimenter3.combo_name']??''}}<br>
                    @endif
                </td>
                <td style="width: 20px">{{ @$item['zlaboratoryDate'] ? date('d-m-Y', @$item['zlaboratoryDate']) : '' }}</td>
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
