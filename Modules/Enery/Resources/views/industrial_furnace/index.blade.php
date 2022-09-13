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
                        <li class="breadcrumb-item active"><a href="">Phòng công nghệ năng lượng</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Quản lý báo cáo đánh giá hiệu suất lò công nghiệp theo Hãng - nhiên liệu</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.industrialFurnaceByManufacture') }}">
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu</p>
                        <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc</p>
                        <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
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
                        <p>Nhiên liệu sử dụng</p>
                        <select class="select2-single form-control" name="use_fuel">
                            <option value="">Chọn nhiên liệu</option>
                            <option value="Dầu" {{ request()->get('use_fuel') == 'Dầu' ? 'selected' : '' }}>Dầu</option>
                            <option value="Gỗ" {{ request()->get('use_fuel') == 'Gỗ' ? 'selected' : '' }}>Gỗ</option>
                            <option value="Khác" {{ request()->get('use_fuel') == 'Khác' ? 'selected' : '' }}>Khác</option>
                            <option value="Khí" {{ request()->get('use_fuel') == 'Khí' ? 'selected' : '' }}>Khí</option>
                            <option value="Sinh khối" {{ request()->get('use_fuel') == 'Sinh khối' ? 'selected' : '' }}>Sinh khối</option>
                            <option value="Than" {{ request()->get('use_fuel') == 'Than' ? 'selected' : '' }}>Than</option>
                        </select>
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
                        <button id="btn-export" class="btn btn-dark {{ !empty($items) ? '' : 'button-hidden' }}" href="{{ route('admin.exportIndustrialFurnaceByManufacture', ['data' => $request]) }}" style="margin-left: 15px;">Xuất file</button>
                    </div>
                </form>
            </div>
            @if (!empty($items))
                <div class="card m-b-30">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-blue">
                                    <tr>
                                        <th>STT</th>
                                        <th>Năm</th>
                                        <th>Tên thiết bị</th>
                                        <th>Kiểu</th>
                                        <th>Chủng loại</th>
                                        <th>Hãng sản xuất</th>
                                        <th>Nhiên liệu sử dụng</th>
                                        <th>Hiệu suất thiết kế (%)</th>
                                        <th>Công suất định mức (T/h)</th>
                                        <th>Công suất thí nghiệm (T/h)</th>
                                        <th>Hiệu suất thí nghiệm (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $key => $item )
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ @$item['zlaboratoryDate'] ? date('d-m-Y', $item['zlaboratoryDate']) : '' }}</td>
                                            <td>{{ @$item['zCI_Device.name'] ? $item['zCI_Device.name'] : '' }}</td>
                                            <td>{{ @$item['zCI_Device.zCI_Device_Kind.zsym'] ? $item['zCI_Device.zCI_Device_Kind.zsym'] : '' }}</td>
                                            <td>{{ @$item['zCI_Device.zCI_Device_Type.zsym'] ? $item['zCI_Device.zCI_Device_Type.zsym'] : '' }}</td>
                                            <td>{{ @$item['zCI_Device.zManufacturer.zsym'] ? $item['zCI_Device.zManufacturer.zsym'] : '' }}</td>
                                            <td>{{ !empty($item['info']['zusefuel.zsym']) ? $item['info']['zusefuel.zsym'] : '' }}</td>
                                            <td>{{ !empty($item['info']['zdesefficien']) ? $item['info']['zdesefficien'] : '' }}</td>
                                            <td>{{ !empty($item['info']['zpower_capacity']) ? $item['info']['zpower_capacity'] : '' }}</td>
                                            <td>{{ !empty($item['experiment']['zOutput_Tets']) ? $item['experiment']['zOutput_Tets'] : '' }}</td>
                                            <td>{{ !empty($item['experiment']['zEfficiency']) ? $item['experiment']['zEfficiency'] : '' }}</td>
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

        $('#btn-export').on('click', function(e){
            e.preventDefault();
            let url = $(this).attr('href');
            const request = $('.module_search').serialize();
            window.location.href = url + `&${request}`;
        });

        $('select[name="devices"]').change(function(){
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
