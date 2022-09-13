<div class="card">
    <div class="card-body min_table list_list">
        <div class="table_minwidth">
            <table class="table table-striped">
                <thead class="thead-dark">
                <tr>
                    <th>Nhóm</th>
                    <th>Khu vực</th>
                    <th>Trạm</th>
                    <th>Ngăn lộ</th>
                    <th>Tên thiết bị</th>
                    <th>Serial</th>
                    <th>Kiểu</th>
                    <th>Dòng điện</th>
                    <th>Cấp chính xác</th>
                    <th>Hãng sản xuất</th>
                    <th>Năm sản xuất</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data as $item)
                    <tr>
                        <td>{{ @$item['zrefnr_dvql.zdisplay.zsym'] }}</td>
                        <td>{{ @$item['zArea.zsym'] }}</td>
                        <td>{{ @$item['zrefnr_td.zsym'] }}</td>
                        <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
                        <td>
                            <a href="{{ route('admin.device.detail', [@$item['handle_id']]) }}">{{ @$item['name'] }}</a>
                        </td>
                        <td>{{ @$item['serial_number'] }}</td>
                        <td>{{ @$item['zCI_Device_Kind.zsym'] }}</td>
                        <td>#</td>
                        <td>#</td>
                        <td>{{ @$item['zManufacturer.zsym'] }}</td>
                        <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                    </tr>
                @endforeach
                @if(!count($data))
                    <tr>
                        <td colspan="11" class="text-center">Không tìm thấy bản ghi nào</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-center mt-3" id="paginate">
    {{ $data->links('pagination::bootstrap-4') }}
</div>
