@extends('layouts.master')

@section('pageTitle', 'Báo cáo CNNL')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="#">Phòng công nghệ năng lượng</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('content')
<div class="row dashboard-index detail-report" style="padding-top: 0px">
    <div class="col-lg-12 col-xl-12">
        <h4 class="m-b-30">Quản lý báo cáo đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn nhiệt ẩm kế</h4>
    </div>
    <div class="col-sm-12 col-xl-12">
        <div class="card m-b-30">
            <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
            </div>
            <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.thiet_bi_hieu_chuan_nhiet_am_ke') }}">
                <div class="form-group form_input form_date">
                    <p>Ngày bắt đầu</p>
                    <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                </div>
                <div class="form-group form_input form_date">
                    <p>Ngày kết thúc</p>
                    <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Thiết bị</p>
                    <input type="text" class="form-control" name="equipment" value="{{ request()->get('equipment') }}"/>
                </div>
                <div class="form-group form_input">
                    <p>Đơn vị quản lý</p>
                    <select class="select2-single form-control" name="dvql_id" id="dvql">
                        <option value="">Đơn vị quản lý</option>
                        @foreach ($dvqls as $dvql)
                            <option value="{{ $dvql['id'] }}" {{ request()->get('dvql_id') == $dvql['id'] ? 'selected' : '' }}>{!! $dvql['zsym'] !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Trạm/Nhà máy</p>
                    <select class="select2-single form-control" name="td_id" id="td">
                        <option value="">Trạm/Nhà máy</option>
                        @foreach ($tds as $td)
                            <option value="{{ $td['id'] }}" {{ request()->get('td_id') == $td['id'] ? 'selected' : '' }}>{!! $td['zsym'] !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Ngăn lộ/Hệ thống</p>
                    <select class="select2-single form-control" name="nl_id" id="nl">
                        <option value="">Ngăn lộ/Hệ thống</option>
                        @foreach ($nls as $nl)
                            <option value="{{ $nl['id'] }}" {{ request()->get('nl_id') == $nl['id'] ? 'selected' : '' }}>{!! $nl['zsym'] !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Kiểu thiết bị</p>
                    <input type="text" class="form-control" name="device_type" value="{{ request()->get('device_type') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Số chế tạo</p>
                    <input type="text" class="form-control" name="manufacturing_number" value="{{ request()->get('manufacturing_number') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Vị trí lắp đặt</p>
                    <input type="text" class="form-control" name="installation_location" value="{{ request()->get('installation_location') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Loại thiết bị</p>
                    <select class="select2-single form-control" name="devices">
                        <option value="">Chọn loại thiết bị</option>
                        @foreach ($types as $key => $val)
                            <option value="{{ $key }}" {{ request()->get('devices') == $key ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Hãng sản xuất</p>
                    <select class="select2-single form-control" name="manufacturer">
                        <option value="">Hãng sản xuất</option>
                        @if (!empty($manufacturer))
                            @foreach ($manufacturer as $key => $val)
                                <option value="{{ $key }}" {{ request()->get('manufacturer') == $key ? 'selected' : '' }}>{!! $val !!}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Người thí nghiệm</p>
                    <input type="text" class="form-control" name="experimenter" value="{{ request()->get('experimenter') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Chủng loại</p>
                    <select class="select2-single form-control" name="species">
                        <option value="">Chủng loại</option>
                        @if (!empty($deviceTypes))
                            @foreach ($deviceTypes as $key => $val)
                                <option value="{{ $key }}" {{ request()->get('species') == $key ? 'selected' : '' }}>{!! $val !!}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 300px;">
                    <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                </div>
                <button id="btnViewReport" type="button" class="btn btn-primary" disabled="" onclick="gotoReport()" >Xem báo cáo</button>
            </form>
        </div>
        @if (!empty($items))
            <div class="card m-b-30">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="thead-blue">
                                <tr>
                                    <th></th>
                                    <th>STT</th>
                                    <th>Tên thiết bị</th>
                                    <th>Ngày làm thí nghiệm</th>
                                    <th>Kiểu thiết bị</th>
                                    <th>Chủng loại</th>
                                    <th>Số chế tạo</th>
                                    <th>Vị trí lắp đặt</th>
                                    <th>Hãng sản xuất</th>
                                    <th>Người thí nghiệm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $key => $item )
                                    <tr>
                                        <td>
                                            <input  id="viewCheck" value="{{ @$item['id'] }}" type="checkbox" class="mr-1">
                                            <input type="hidden" value="{{ !empty($item['zCI_Device.name']) ? $item['zCI_Device.name'] : '' }}">
                                        </td>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item['zCI_Device.name'] ??'' }}</td>
                                        <td>{{ @$item['zlaboratoryDate'] ? date('d-m-Y', @$item['zlaboratoryDate']) : '' }}</td>
                                        <td>{!! @$item['zCI_Device.zCI_Device_Kind.zsym'] ??'' !!}</td>
                                        <td>{!! @$item['zCI_Device_Type.zsym'] ??''!!}</td>
                                        <td>{!! @$item['zCI_Device.serial_number'] ??'' !!}</td>
                                        <td>{!! @$item['zvitrilapdat'] ??''!!}</td>
                                        <td>
                                            {!! @$item['zManufacturer.zsym'] ??'' !!}
                                        </td>
                                        <td>
                                            {{ trim(implode(' ', [@$item['zExperimenter.last_name'], @$item['zExperimenter.first_name'], @$item['zExperimenter.middle_name']])) }} <br>
                                            {{ trim(implode(' ', [@$item['zExperimenter1.last_name'], @$item['zExperimenter1.first_name'], @$item['zExperimenter1.middle_name']])) }} <br>
                                            {{ trim(implode(' ', [@$item['zExperimenter2.last_name'], @$item['zExperimenter2.first_name'], @$item['zExperimenter2.middle_name']])) }} <br>
                                            {{ trim(implode(' ', [@$item['zExperimenter3.last_name'], @$item['zExperimenter3.first_name'], @$item['zExperimenter3.middle_name']])) }} <br>
                                        </td>
                                    </tr>
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
        $('.module_search').submit(function(event) {
            event.preventDefault();

            if ($('select[name="devices"]').val() === '') {
                toastr["error"]("Loại thiết bị không được để trống");
            } else {
                $(this).unbind('submit').submit();
            }
        });

        $(function () {
            $('input[type=checkbox]').on('change', function (e) {
                let totalChecked = $('input[type=checkbox]:checked').length;
                if (totalChecked === 0) {
                    $(this).prop('checked', false);
                    $('#btnViewReport').attr('disabled', 'disabled')
                }
                else {
                    $('#btnViewReport').removeAttr('disabled')
                }
            });
        });

        function gotoReport() {
            let ids = [],
                deviceNames = [];
            $('#viewCheck[type=checkbox]:checked').each(function(){
                ids.push($(this).val());
                deviceNames.push($(this).next().val());
            });

            let uniqueItems = deviceNames.filter(function(value, index, self){
                return self.indexOf(value) === index;
            });
            const request = $('.module_search').serialize();
            if (uniqueItems.length > 1) {
                toastr["error"]("Các biên bản phải thuộc cùng 1 thiết bị");
            } else {
                window.location.href = '{{ route('admin.thiet_bi_hieu_chuan_nhiet_am_ke_preview') }}?ids=' +  ids.join(',')+'&'+request;
            }
        }

        $('select[name="devices"]').change(function(){
            $.ajax({
                url: '{{ route('admin.objectByDeviceType') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    type: $(this).val(),
                    obj: 'zManufacturer'
                }
            }).done(function(result) {
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }

                if (typeof response['success'] !== 'undefined') {
                    let html = '<option value="">Hãng sản xuất</option>';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('select[name="manufacturer"]').html(html);
                }
            });

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
                    $('select[name="species"]').html(html);
                }
            });
        });
        function getDataObject(id, obj, elementAppend, optionBlack){
            $(elementAppend).attr('disabled', 'disabled');
            $.ajax({
                url: '{{ route("admin.getDataObject") }}',
                method: "GET",
                data: { id, obj },
                dataType: 'html',
            }).done(function(result) {
                $(elementAppend).removeAttr('disabled');
                let response = JSON.parse(result);
                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }
                if (typeof response['success'] !== 'undefined') {
                    let html = `<option value="">${optionBlack}</option>`;
                    $.each(response['data'], function(index, value){
                        html += `<option value=${value['id']}>${value['zsym']}</option>`;
                    });
                    $(elementAppend).html(html);
                }
            });
        }
        $('#dvql').on('change', function(){
            const id = $(this).val();
            $('#nl').html('<option value="">Ngăn lộ/Hệ thống</option>');
            if( !id ){
                $('#td').html('<option value="">Trạm/Nhà máy</option>');
            }else{
                getDataObject(id, 'zTD', '#td', 'Trạm/Nhà máy');
            }
        });
        $('#td').on('change', function(){
            const id = $(this).val();
            if( !id ){
                $('#nl').html('<option value="">Ngăn lộ/Hệ thống</option>');
            }else{
                getDataObject(id, 'zNL', '#nl', 'Ngăn lộ/Hệ thống')
            }
            
        });
    </script>
@endpush
