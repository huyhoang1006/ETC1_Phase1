<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th></th>
            <th>Đơn vị quản lý</th>
            <th>Trạm</th>
            <th>Tên thiết bị</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>
                <input id="viewCheck" type="checkbox" value="{{ $item['id'] }}" >
            </td>
            <td>{{ @$item['zrefnr_dvql.zsym'] }}</td>
            <td>{{ @$item['zrefnr_td.zsym'] }}</td>
            <td>{{ @$item['name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>