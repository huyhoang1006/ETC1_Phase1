<table class="table table-striped">
    <thead class="thead-dark" align="center">
        <tr>
            <th>STT</th>
            @foreach ($types as $type)
                <th>{{ $type }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody align="center">
        @for ($i = 1; $i <= count(max($dataArr)); $i++)
            <tr>
                <td>{{ $i }}</td>
                @foreach ($types as $type)
                    <td>{{ !empty($dataArr[$type][$i - 1]) ? $dataArr[$type][$i - 1] : '' }}</td>
                @endforeach
            </tr>
        @endfor
    </tbody>
</table>
