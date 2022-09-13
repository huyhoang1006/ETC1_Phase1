<table class="table table-striped">
    <thead class="thead-blue">
    <tr>
        <th></th>
        <th>Thiết bị</th>
        <th>Khu vực</th>
        <th>Trạm/Nhà máy</th>
        <th>Ngăn lộ/Hệ thống</th>
        <th>Hãng sản xuất</th>
        <th>Kiểu</th>
        <th>Số chế tạo</th>
        <th>Năm sản xuất</th>
        <th>Nước sản xuất</th>
    </tr>
    </thead>
    <tbody>
        @foreach ($reports as $report)
            <tr>
                <td>
                    <input value="{{ !empty($report['id']) ? $report['id'] : '' }}" type="radio" name="report_id">
                </td>
                <td>{{ !empty($report['name']) ? $report['name'] : '' }}</td>
                <td>{{ !empty($report['zCustomer.zsym']) ? $report['zCustomer.zsym'] : '' }}</td>
                <td>{{ !empty($report['zrefnr_td.zsym']) ? $report['zrefnr_td.zsym'] : '' }}</td>
                <td>{{ !empty($report['zrefnr_nl.zsym']) ? $report['zrefnr_nl.zsym'] : '' }}</td>
                <td>{{ !empty($report['zManufacturer.zsym']) ? $report['zManufacturer.zsym'] : '' }}</td>
                <td>{{ !empty($report['zCI_Device_Kind.zsym']) ? $report['zCI_Device_Kind.zsym'] : '' }}</td>
                <td>{{ !empty($report['serial_number']) ? $report['serial_number'] : '' }}</td>
                <td>{{ !empty($report['zYear_of_Manafacture.zsym']) ? $report['zYear_of_Manafacture.zsym'] : '' }}</td>
                <td>{{ !empty($report['zCountry.name']) ? $report['zCountry.name'] : '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
