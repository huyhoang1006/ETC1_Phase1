@extends('layouts.master')
@section('pageTitle', 'BÁO CÁO THỐNG KÊ CÔNG NGHỆ NĂNG LƯỢNG')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="">Phòng đo lường</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Báo cáo thống kê danh sách thiết bị</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;align-items: flex-end;" method="get" action="{{ route('admin.deviceListingReport') }}">
                    <div class="form-group form_input form_date">
                        <p>Đơn vị quản lý</p>
                        <select class="select2-single form-control" name="zrefnr_dvql_ids[]" id="zrefnr_dvql_ids">
                            <option value="">Chọn đơn vị quản lý</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit['id'] }}" {{ !empty(request()->get('zrefnr_dvql_ids')) && in_array($unit['id'], request()->get('zrefnr_dvql_ids')) ? 'selected' : '' }}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Trạm</p>
                        <select class="select2-single form-control" name="td_name" id="td_name">
                            <option value="">Chọn trạm</option>
                            @foreach ($tds as $td)
                                <option value="{{ $td['zsym'] }}" {{ request()->get('td_name') == $td['zsym'] ? 'selected' : '' }}>{!! $td['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Kiểu</p>
                        <select class="select2-single form-control" name="zCI_Device_Kind" id="zCI_Device_Kind">
                            <option value="">Chọn kiểu thiết bị</option>
                            @foreach ($deviceModel as $model)
                                <option value="{{ $model['zsym'] }}" {{ request()->get('zCI_Device_Kind') == $model['zsym'] ? 'selected' : '' }}>{!! $model['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Chủng loại</p>
                        <select class="select2-single form-control" name="zCI_Device_Type_id">
                            <option value="">Chủng loại</option>
                            @if (!empty($deviceTypes))
                                @foreach ($deviceTypes as $key => $val)
                                    <option value="{{ $key }}" {{ request()->get('zCI_Device_Type_id') == $key ? 'selected' : '' }}>{!! $val !!}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Năm sản xuất</p>
                        <select class="select2-single form-control" name="zYear_of_Manafacture">
                            <option value="">Chọn năm sản xuất</option>
                            @foreach ($years as $year)
                                <option value="{{ $year['zsym'] }}" {{ request()->get('zYear_of_Manafacture') == $year['zsym'] ? 'selected' : '' }}>{!! $year['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Đơn vị quản lý điểm đo</p>
                        <select class="select2-single form-control" name="zdvquanlydiemdosrel" id="zdvquanlydiemdosrel">
                            <option value="">Chọn đơn vị quản lý điểm đo</option>
                            @foreach ($units as $unit)
                                <option value="{!! $unit['zsym'] !!}" {!! request()->get('zdvquanlydiemdosrel') ==  $unit['zsym'] ? 'selected' : '' !!}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Đơn vị giao điện năng</p>
                        <select class="select2-single form-control" name="zdvgiaodiennangsrel" id="zdvgiaodiennangsrel">
                            <option value="">Chọn đơn vị giao điện năng</option>
                            @foreach ($deliveryUnit as $unit)
                                <option value="{!! $unit['zsym'] !!}" {!! request()->get('zdvgiaodiennangsrel') ==  $unit['zsym'] ? 'selected' : '' !!}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Đơn vị nhận điện năng</p>
                        <select class="select2-single form-control" name="zdvnhandiennangsrel" id="zdvnhandiennangsrel">
                            <option value="">Chọn đơn vị nhận điện năng</option>
                            @foreach ($receivingUnit as $unit)
                                <option value="{!! $unit['zsym'] !!}" {!! request()->get('zdvnhandiennangsrel') ==  $unit['zsym'] ? 'selected' : '' !!}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Loại điểm đo</p>
                        <select class="select2-single form-control" name="zloaidiemdosrel" id="zloaidiemdosrel">
                            <option value="">Chọn loại điểm đo</option>
                            @foreach ($typeMeasuring as $type)
                                <option value="{!! $type['zsym'] !!}" {!! request()->get('zloaidiemdosrel') ==  $type['zsym'] ? 'selected' : '' !!}>{!! $type['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Thiết bị</p>
                        <select name="type_id" class="select2-single form-control">
                            <option value="">Thiết bị</option>
                            <option value="1004903" {{ request()->get('type_id') == 1004903 ? 'selected' : '' }}>Công tơ</option>
                            <option value="1002783" {{ request()->get('type_id') == 1002783 ? 'selected' : '' }}>Máy biến dòng</option>
                            <option value="1002779" {{ request()->get('type_id') == 1002779 ? 'selected' : '' }}>Máy biến điện áp</option>
                        </select>
                        <input type="hidden" name="type_name"value="{{ request()->get('type_name') }}">
                    </div>
                    <div class="form-group form_submit" style="display: flex;align-items: center;width: 300px;">
                        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        <button id="btn-export" href="{{ route('admin.deviceListingExport', ['data' => $request]) }}" class="btn btn-dark {{ !empty($deviceArr) ? '' : 'button-hidden' }}" style="margin-left: 15px;">Xuất file</button>
                    </div>
                </form>
            </div>
            @if (!empty($deviceArr))
                <div class="card m-b-30">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-blue">
                                    <tr>
                                        <th>STT</th>
                                        <th>Đơn vị quản lý (Customer)</th>
                                        <th>Khu vực (Tỉnh/Thành phố) (Area)</th>
                                        <th>Trạm/Nhà máy (Substation/Plant)</th>
                                        <th>Ngăn lộ/Hệ thống/Tổ máy (Bay/System/Unit):</th>
                                        <th>Thiết bị (Equipment)</th>
                                        <th>Chủng loại (Type)</th>
                                        <th>Hãng sản xuất (Manufacturer)</th>
                                        <th>Kiểu thiết bị(Model)</th>
                                        <th>Số chế tạo (Serial Number)</th>
                                        <th>Năm sản xuất (Year of Manufacture)</th>
                                        <th>Nước sản xuất (Country/Origin)</th>
                                        @if (empty($request['type_id']) || $request['type_id'] == 1004903)
                                            <th>Đơn vị quản lý điểm đo</th>
                                            <th>Đơn vị giao điện năng</th>
                                            <th>Đơn vị nhận điện năng</th>
                                            <th>Vị trí địa lý</th>
                                            <th>Loại điểm đo</th>
                                            <th>Ngày nghiệm thu tĩnh</th>
                                            <th>Ngày nghiệm thu mang tải</th>
                                            <th>Thông tin TU sử dụng</th>
                                            <th>Thông tin TI sử dụng</th>
                                            <th>Hàng kẹp mạch dòng</th>
                                            <th>Hàng kẹp mạch áp</th>
                                            <th>Điện áp (voltage)</th>
                                            <th>Dòng điện (current)</th>
                                            <th>Cấp chính xác P</th>
                                            <th>Cấp chính xác Q</th>
                                            <th>Hằng số xung</th>
                                            <th>Tỷ số TU cài đặt trong công tơ</th>
                                            <th>Tỷ số TI cài đặt trong công tơ</th>
                                            <th>Số lần lập trình</th>
                                            <th>Thời gian lập trình lần cuối</th>
                                            <th>Kết quả kiểm định</th>
                                            <th>Cảnh báo</th>
                                            <th>Hạn kiểm định (Valid until)</th>
                                            <th>Số tem kiểm định</th>
                                            <th>Số seri tem kiểm định</th>
                                        @elseif ($request['type_id'] == 1002783)
                                            <th>Hạn kiểm định (Valid until)</th>
                                            <th>Số tem kiểm định</th>
                                            <th>Số seri tem kiểm định</th>
                                            <th>Kết quả kiểm định</th>
                                            <th>Tỷ số biến dòng</th>
                                            <th>Cấp chính xác</th>
                                            <th>Dung lượng</th>
                                            <th>Niêm phong nắp boóc</th>
                                        @else
                                            <th>Hạn kiểm định (Valid until)</th>
                                            <th>Số tem kiểm định</th>
                                            <th>Số seri tem kiểm định</th>
                                            <th>Kết quả kiểm định</th>
                                            <th>Tỷ số biến áp</th>
                                            <th>Cấp chính xác</th>
                                            <th>Dung lượng</th>
                                            <th>Giá trị tụ</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $count = 1;
                                    ?>
                                    @foreach ($deviceArr as $device)
                                        @if (!empty($request['zdvquanlydiemdosrel']) && (empty($device['info']['zdvquanlydiemdosrel.zsym']) || strpos($device['info']['zdvquanlydiemdosrel.zsym'], $request['zdvquanlydiemdosrel']) === false))
                                            @continue
                                        @endif
                                        @if (!empty($request['zdvgiaodiennangsrel']) && (empty($device['info']['zdvgiaodiennangsrel.zsym']) || strpos($device['info']['zdvgiaodiennangsrel.zsym'], $request['zdvgiaodiennangsrel']) === false))
                                            @continue
                                        @endif
                                        @if (!empty($request['zdvnhandiennangsrel']) && (empty($device['info']['zdvnhandiennangsrel.zsym']) || strpos($device['info']['zdvnhandiennangsrel.zsym'], $request['zdvnhandiennangsrel']) === false))
                                            @continue
                                        @endif
                                        @if (!empty($request['zloaidiemdosrel']) && (empty($device['info']['zloaidiemdosrel.zsym']) || strpos($device['info']['zloaidiemdosrel.zsym'], $request['zloaidiemdosrel']) === false))
                                            @continue
                                        @endif
                                        <tr>
                                            <td>{{ $count }}</td>
                                            <td>{{ !empty($device['zrefnr_dvql.zsym']) ? $device['zrefnr_dvql.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['zArea.zsym']) ? $device['zArea.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['zrefnr_td.zsym']) ? $device['zrefnr_td.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['zrefnr_nl.zsym']) ? $device['zrefnr_nl.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['name']) ? $device['name'] : '' }}</td>
                                            <td>{{ !empty($device['zCI_Device_Type.zsym']) ? $device['zCI_Device_Type.zsym'] : '' }}</td>
                                            <td>{!! !empty($device['zManufacturer.zsym']) ? $device['zManufacturer.zsym'] : '' !!}</td>
                                            <td>{{ !empty($device['zCI_Device_Kind.zsym']) ? $device['zCI_Device_Kind.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['serial_number']) ? $device['serial_number'] : '' }}</td>
                                            <td>{{ !empty($device['zYear_of_Manafacture.zsym']) ? $device['zYear_of_Manafacture.zsym'] : '' }}</td>
                                            <td>{{ !empty($device['zCountry.name']) ? $device['zCountry.name'] : '' }}</td>
                                            @if (empty($request['type_id']) || $request['type_id'] == 1004903)
                                                <td>{{ !empty($device['info']['zdvquanlydiemdosrel.zsym']) ? $device['info']['zdvquanlydiemdosrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zdvgiaodiennangsrel.zsym']) ? $device['info']['zdvgiaodiennangsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zdvnhandiennangsrel.zsym']) ? $device['info']['zdvnhandiennangsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zvitridialy']) ? $device['info']['zvitridialy'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zloaidiemdosrel.zsym']) ? $device['info']['zloaidiemdosrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zngaynttinhdate']) ? date('d/m/Y', $device['info']['zngaynttinhdate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['zngayntmangtaidate']) ? date('d/m/Y', $device['info']['zngayntmangtaidate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['zTUinform']) ? $device['info']['zTUinform'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zTIinform']) ? $device['info']['zTIinform'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zhangkepmachdong']) ? $device['info']['zhangkepmachdong'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zhangkepmachap']) ? $device['info']['zhangkepmachap'] : '' }}</td>
                                                <td>{!! !empty($device['info']['zdienapsrel.zsym']) ? $device['info']['zdienapsrel.zsym'] : '' !!}</td>
                                                <td>{{ !empty($device['info']['zdongdiensrel.zsym']) ? $device['info']['zdongdiensrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zcapcxpsrel.zsym']) ? $device['info']['zcapcxpsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zcapcxqsrel.zsym']) ? $device['info']['zcapcxqsrel.zsym'] : '' }}</td>
                                                <td>{!! !empty($device['info']['zhangsosxungsrel.zsym']) ? $device['info']['zhangsosxungsrel.zsym'] : '' !!}</td>
                                                <td>{{ !empty($device['info']['ztysotu']) ? $device['info']['ztysotu'] : '' }}</td>
                                                <td>{{ !empty($device['info']['ztysoti']) ? $device['info']['ztysoti'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zsolanlaptrinh']) ? $device['info']['zsolanlaptrinh'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zlasttimelaptrinhdate']) ? date('d/m/Y', $device['info']['zlasttimelaptrinhdate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zcanhbao']) ? $device['info']['zcanhbao'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['zsotemkiemdinh']) ? $device['info']['zsotemkiemdinh'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                                            @elseif ($request['type_id'] == 1002783)
                                                <td>{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['ztemkiemdinhnum']) ? $device['info']['ztemkiemdinhnum'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['ztysobiendongstr']) ? $device['info']['ztysobiendongstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zcapchinhxacstr']) ? $device['info']['zcapchinhxacstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zdungluonstr']) ? $device['info']['zdungluonstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zniemphongnapbocstr']) ? $device['info']['zniemphongnapbocstr'] : '' }}</td>
                                            @else
                                                <td>{{ !empty($device['info']['zhankiemdinhdate']) ? date('d/m/Y', $device['info']['zhankiemdinhdate']) : '' }}</td>
                                                <td>{{ !empty($device['info']['ztemkiemdinhnum']) ? $device['info']['ztemkiemdinhnum'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zseritemkiemding']) ? $device['info']['zseritemkiemding'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zkqkiemdinhsrel.zsym']) ? $device['info']['zkqkiemdinhsrel.zsym'] : '' }}</td>
                                                <td>{{ !empty($device['info']['ztysobienapstr']) ? $device['info']['ztysobienapstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zcapchinhxacstr']) ? $device['info']['zcapchinhxacstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zdungluonstr']) ? $device['info']['zdungluonstr'] : '' }}</td>
                                                <td>{{ !empty($device['info']['zgiatritustr']) ? $device['info']['zgiatritustr'] : '' }}</td>
                                            @endif
                                        </tr>
                                        <?php
                                            $count++;
                                        ?>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#btn-export').on('click', function(e){
            e.preventDefault();
            let url = $(this).attr('href');
            const request = $('.module_search').serialize();
            window.location.href = url + `&${request}`;
        });

        $('.module_search').submit(function(event) {
            event.preventDefault();

            if ($('select[name="type_id"]').val() === '') {
                toastr["error"]("Loại thiết bị không được để trống");
            } else {
                $(this).unbind('submit').submit();
            }
        });

        $('select[name="type_id"]').change(function(){
            $('input[name="type_name"]').val($(this).find('option:selected').text());

            $.ajax({
                url: '{{ route('admin.objectByDeviceType') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    type: $(this).val(),
                    obj: 'zCI_Device_Type'
                }
            }).done(function(result) {
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }

                if (typeof response['success'] !== 'undefined') {
                    let html = '<option value="">Chủng loại</option>';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('select[name="zCI_Device_Type_id"]').html(html);
                }
            });
        });

        $('#zrefnr_dvql_ids').on('change', function(){
            $('#td_name').prop('disabled', true);
            const zrefnr_dvql_ids = [$(this).val()];
            $.ajax({
                url: '{{ route('admin.measure.ajaxGetTD') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    zrefnr_dvql_ids
                }
            }).done(function(result) {
                $('#td_name').prop('disabled', false);
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }

                if (typeof response['success'] !== 'undefined') {
                    let html = '<option value="">Chọn trạm</option>';
                    $.each(response['data'], function(index, value){
                        html += `<option value="${value['zsym']}">${value['zsym']}</option>`;
                    });
                    $('#td_name').html(html);
                }
            });
        });
    </script>
@endpush
