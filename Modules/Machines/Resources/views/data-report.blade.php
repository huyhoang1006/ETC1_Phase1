<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th></th>
            <th>Ngày thí nghiệm</th>
            <th>Loại biên bản</th>
            <th>Tên biên bản</th>
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
                <td><input id="viewCheck" type="checkbox" value="{{ $item['id'] }}" ></td>
                <td>{{ @$item['zlaboratoryDate'] }}</td>
                <td>{{ @$item['class.type'] }}</td>
                <td>{{ @$item['name'] }}</td>
                <td>{{ @$item['zCI_Device.name'] }}</td>
                <td>{{ @$item['zCI_Device.zArea.zsym'] }}</td>
                <td>{{ @$item['zrefnr_td.zsym'] }}</td>
                <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
                <td>{{ @$item['zCI_Device_Type.zsym'] }}</td>
                <td>{{ @$item['zManufacturer.zsym'] }}</td>
                <td>{{ @$item['zCI_Device.zCI_Device_Kind.zsym'] }}</td>
                <td>{{ @$item['zCI_Device.serial_number'] }}</td>
                <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                <td>{{ @$item['zCountry.name'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
