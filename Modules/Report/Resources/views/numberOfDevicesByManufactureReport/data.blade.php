<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th>STT</th>
            <th>Tên hãng</th>
            <th>Thiết bị</th>
            <th>Số lượng thiết bị trên hãng</th>
            <th>Đơn vị</th>
        </tr>
    </thead>
    <tbody align="center">
        <?php
            $count = 1;
        ?>
        @foreach ($dataArr as $key => $val)
            <tr>
                <td>{{ $count }}</td>
                <td>{{ explode('_', $key)[0] }}</td>
                <td>{{ explode('_', $key)[1] }}</td>
                <td>{{ $val }}</td>
                <td>Bộ</td>
            </tr>
            <?php
                $count++;
            ?>
        @endforeach
        <tr>
            <td>{{ $count }}</td>
            <td colspan="2">Tổng số</td>
            <td>{{ array_sum($dataArr) }}</td>
            <td>Bộ</td>
        </tr>
    </tbody>
</table>
