@extends('layouts.master')

@section('pageTitle', 'BÁO CÁO THỐNG KÊ ĐO LƯỜNG')

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
            <h4 class="m-b-30">Báo cáo số lượng thiết bị của từng năm sản xuất</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;align-items: flex-end;" method="get" action="{{ route('admin.equipmentEveryYear') }}">
                    <div class="form-group form_input">
                        <p>Loại thiết bị</p>
                        <select class="select2-single form-control" name="type_device_id">
                            <option value="">Loại thiết bị</option>
                            <option @if(!empty($request['type_device_id']) && $request['type_device_id'] == '1004903') selected @endif value="1004903" >Công tơ</option>
                            <option @if(!empty($request['type_device_id']) && $request['type_device_id'] == '1002783') selected @endif value="1002783">Máy biến dòng</option>
                            <option @if(!empty($request['type_device_id']) && $request['type_device_id'] == '1002779') selected @endif value="1002779">Máy biến điện áp</option>
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Năm sản xuất bắt đầu</p>
                        <select class="select2-single form-control" name="year_from">
                            <option value="">Chọn năm sản xuất</option>
                            @for($i=$year; $i >= 1900;$i -= 1)
                                <option @if(!empty($request['year_from']) && $request['year_from'] == $i) selected @endif value="{{$i}}">{{$i}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Năm sản xuất kết thúc</p>
                        <select class="select2-single form-control" name="year_to">
                            <option value="">Chọn năm sản xuất</option>
                            @for($i=$year; $i >= 1900;$i -= 1)
                                <option @if(!empty($request['year_to']) && $request['year_to'] == $i) selected @endif value="{{$i}}">{{$i}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Hãng sản xuất</p>
                        <select class="select2-single form-control" name="manufacturer_id">
                            <option value="">Hãng sản xuất</option>
                            @if (!empty($manufacturer))
                                @foreach ($manufacturer as $key => $val)
                                    <option value="{{ $key }}" {{ request()->get('manufacturer_id') == $key ? 'selected' : '' }}>{!! $val !!}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Đơn vị quản lý</p>
                        <select class="select2-single form-control" name="zrefnr_dvql">
                            <option value="">Chọn đơn vị quản lý</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit['id'] }}" {{ request()->get('zrefnr_dvql') == $unit['id'] ? 'selected' : '' }}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_submit" style="display: flex;align-items: center;width: 300px;">
                        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        <button id="btn-export" class="btn btn-dark {{ !empty($items) ? '' : 'button-hidden' }}" href="{{ route('admin.exportEquipmentEveryYear', ['data' => $request]) }}" style="margin-left: 15px;">Xuất file</button>
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
                                        <th>Loại thiết bị</th>
                                        <th>Chủng loại</th>
                                        <th>Đơn vị quản lý</th>
                                        <th>Hãng sản xuất</th>
                                        <th>Năm sản xuất</th>
                                        <th>Số lượng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{$item['class.type']??''}}</td>
                                            <td>{{$item['zCI_Device_Type.zsym']??''}}</td>
                                            <td>{{$item['zrefnr_dvql.zsym']??''}}</td>
                                            <td>{!!$item['zManufacturer.zsym']??''!!}</td>
                                            <td>{{$item['zYear_of_Manafacture.zsym']??''}}</td>
                                            <td>{{$item['count']??''}}</td>
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
         $('#btn-export').on('click', function(e){
            e.preventDefault();
            let url = $(this).attr('href');
            const request = $('.module_search').serialize();
            window.location.href = url + `&${request}`;
        });

        $('.module_search').submit(function(event) {
            event.preventDefault();

            if ($('select[name="type_device_id"]').val() === '') {
                toastr["error"]("Loại thiết bị không được để trống");
            } else if ($('select[name="year_from"]').val() !== '' && $('select[name="year_to"]').val() !== '' && $('select[name="year_from"]').val() > $('select[name="year_to"]').val()) {
                toastr["error"]("Thời gian bắt đầu phải nhỏ hơn hoặc bằng thời gian kết thúc");
            } else {
                $(this).unbind('submit').submit();
            }
        });

        $('select[name="type_device_id"]').change(function(){
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
                    $('select[name="manufacturer_id"]').html(html);
                }
            });
        });
    </script>
@endpush
