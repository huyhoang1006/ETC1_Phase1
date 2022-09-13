<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th>STT</th>
            <th>Nội dung</th>
            <th>ĐVT</th>
            <th>Số lượng thực hiện</th>
        </tr>
    </thead>
    <tbody align="center">
        <?php
            $count = 1;
            $passIndex = -1;
        ?>
        @foreach ($dataArr as $key => $val)
            <?php
                $num = $val['count'];
                if (!empty($dataArr[$key + 1]['name']) && $dataArr[$key]['name'] == $dataArr[$key + 1]['name']) {
                    $num += $dataArr[$key + 1]['count'];
                    $passIndex = $key + 1;
                }
                if ($passIndex == $key) {
                    $passIndex = -1;
                    continue;
                }
            ?>
            <tr>
                <td>{{ $count }}</td>
                <td style="text-align: left;">{{ $val['name'] }}</td>
                <td>{{ $val['unit'] }}</td>
                <td>{{ $num }}</td>
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
