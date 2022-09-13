<head>
    <meta charset="utf-8">
    <meta http-equiv=Content-Type content=text/html;charset=utf-8>
</head>
<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th>STT</th>
            <th>Năm</th>
            <th>Tên thiết bị</th>
            <th>Kiểu</th>
            <th>Chủng loại</th>
            <th>Hãng sản xuất</th>
            <th>Nhiên liệu sử dụng</th>
            <th>Hiệu suất thiết kế (%)</th>
            <th>Công suất định mức (T/h)</th>
            <th>Công suất thí nghiệm (T/h)</th>
            <th>Hiệu suất thí nghiệm (%)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $key => $item )
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ @$item['zlaboratoryDate'] ? date('d-m-Y', $item['zlaboratoryDate']) : '' }}</td>
                <td>{{ @$item['zCI_Device.name'] ? $item['zCI_Device.name'] : '' }}</td>
                <td>{{ @$item['zCI_Device.zCI_Device_Kind.zsym'] ? $item['zCI_Device.zCI_Device_Kind.zsym'] : '' }}</td>
                <td>{{ @$item['zCI_Device.zCI_Device_Type.zsym'] ? $item['zCI_Device.zCI_Device_Type.zsym'] : '' }}</td>
                <td>{{ @$item['zCI_Device.zManufacturer.zsym'] ? $item['zCI_Device.zManufacturer.zsym'] : '' }}</td>
                <td>{{ !empty($item['info']['zusefuel.zsym']) ? $item['info']['zusefuel.zsym'] : '' }}</td>
                <td>{{ !empty($item['info']['zdesefficien']) ? $item['info']['zdesefficien'] : '' }}</td>
                <td>{{ !empty($item['info']['zpower_capacity']) ? $item['info']['zpower_capacity'] : '' }}</td>
                <td>{{ !empty($item['experiment']['zOutput_Tets']) ? $item['experiment']['zOutput_Tets'] : '' }}</td>
                <td>{{ !empty($item['experiment']['zEfficiency']) ? $item['experiment']['zEfficiency'] : '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
