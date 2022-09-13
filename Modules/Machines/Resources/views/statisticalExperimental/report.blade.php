<table class="table table-striped">
    <thead class="thead-blue">
        <tr>
            <th>Đơn vị quản lý</th>
            <th>Trạm</th>
            <th>Tên thiết bị</th>
            <th>Số lần được thí nghiệm</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>{{ @$item['zrefnr_dvql.zsym'] }}</td>
            <td>{{ @$item['zrefnr_td.zsym'] }}</td>
            <td>{{ @$item['name'] }}</td>
            <td>{{ @$item['number'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>