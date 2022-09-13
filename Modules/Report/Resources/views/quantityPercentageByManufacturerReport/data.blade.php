<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th>STT</th>
            <th>Hãng sản xuất</th>
            <th>Tên chủng loại</th>
            <th>Tỷ lệ số lượng thiết bị (tính theo %)</th>
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
                <td>{{ round($val / array_sum($dataArr) * 100, 2) }}%</td>
            </tr>
            <?php
                $count++;
            ?>
        @endforeach
    </tbody>
</table>
