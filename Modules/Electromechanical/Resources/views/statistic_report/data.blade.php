<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th>STT</th>
            <th>Đơn vị sử dụng</th>
            <th>Trạm/ Nhà máy</th>
            <th>Thiết bị</th>
            <th>Kiểu thí nghiệm</th>
            <th>Kiểu thiết bị</th>
            <th>Hãng sản xuất</th>
            <th>Người thí nghiệm</th>
            <th>Ngày thí nghiệm</th>
        </tr>
    </thead>
    <tbody align="center">
        <?php
            $count = 1;
        ?>
        @foreach ($dataArr as $key => $item)
            <tr>
                <td>{{ $count }}</td>
                <td>{{$item['zrefnr_dvql.zsym']??''}}</td>
                <td>{{$item['zrefnr_td.zsym']??''}}</td>
                <td>{{$item['zCI_Device.name']??''}}</td>
                <td>{{$item['zCI_Device.zStage.zsym']??''}}</td>
                <td>{{$item['zCI_Device_Kind.zsym']??''}}</td>
                <td>{{$item['zManufacturer.zsym']??''}}</td>
                <td>
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
                <td>{{ @$item['zlaboratoryDate'] ? date('d-m-Y', @$item['zlaboratoryDate']) : '' }}</td>
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
