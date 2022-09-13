<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th>Thiết bị</th>
            <th>Khu vực</th>
            <th>Trạm/ Nhà máy</th>
            <th>Ngăn lộ/ hệ thống</th>
            <th>Chủng loại</th>
            <th>Hãng sản xuất</th>
            <th>Kiểu</th>
            <th>Số chế tạo</th>
            <th>Năm sản xuất</th>
            <th>Nước sản xuất</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>
                <a href="#" class="filter-report" data-id="{{ $item['id'] }}">{{ @$item['name'] }}</a>
            </td>
            <td>{{ @$item['zArea.zsym'] }}</td>
            <td>{{ @$item['zrefnr_td.zsym'] }}</td>
            <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
            <td> {{ @$item['zCI_Device_Type.zsym'] }} </td>
            <td>{{ @$item['zManufacturer.zsym'] }}</td>
            <td>{{ @$item['zCI_Device_Kind.zsym'] }}</td>
            <td>{{ @$item['serial_number'] }}</td>
            <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
            <td>{{ @$item['zCountry.name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>