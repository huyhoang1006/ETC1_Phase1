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
            <h4 class="m-b-30">Báo cáo danh sách thiết bị theo hạn kiểm định</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;align-items: flex-end;" method="get" action="{{ route('admin.equipmentUnderInspection') }}">
                    <div class="form-group form_input">
                        <p>Loại thiết bị</p>
                        <select class="select2-single form-control" name="type_device">
                            <option value="">Loại thiết bị</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Công tơ') selected @endif value="Công tơ" >Công tơ</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Máy biến dòng') selected @endif value="Máy biến dòng">Máy biến dòng</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Máy biến điện áp') selected @endif value="Máy biến điện áp">Máy biến điện áp</option>
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu hạn kiểm định</p>
                        <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc hạn kiểm định</p>
                        <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
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
                    <div class="form-group form_input form_date">
                        <p>Đơn vị quản lý điểm đo</p>
                        <select class="select2-single form-control" name="zdvquanlydiemdosrel">
                            <option value="">Chọn đơn vị quản lý điểm đo</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit['id'] }}" {{ request()->get('zdvquanlydiemdosrel') == $unit['id'] ? 'selected' : '' }}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_submit" style="display: flex;align-items: center;">
                        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        <a id="btn-export" href="{{ route('admin.exportEquipmentUnderInspection', ['data' => $request]) }}" style="width: 100%;height: 38px;box-shadow: none;padding-left: 0;padding-right: 0;background: #1a4796;color: #fff;{{ !empty($items) ? 'display: flex' : 'display: none' }};justify-content: center;align-items: center;border-radius: 3px;font-size: 15px;margin-left: 10px;border: 1px solid #141d46;">Xuất file</a>
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
                                        <th>Trạm/ nhà máy</th>
                                        <th>Ngăn lộ</th>
                                        <th>Thiết bị</th>
                                        <th>Chủng loại</th>
                                        <th>Hạn kiểm định</th>
                                        <th>Vị trí địa lý</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $key => $item )
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ !empty($item['zrefnr_td.zsym']) ? $item['zrefnr_td.zsym'] : '' }}</td>
                                            <td>{{ !empty($item['zrefnr_nl.zsym']) ? $item['zrefnr_nl.zsym'] : '' }}</td>
                                            <td>{{ !empty($item['name']) ? $item['name'] : '' }}</td>
                                            <td>{{ !empty($item['zCI_Device_Type.zsym']) ? $item['zCI_Device_Type.zsym'] : '' }}</td>
                                            <td>{{ !empty($item['zhankiemdinhdate']) ? date('d-m-Y', $item['zhankiemdinhdate']) : '' }}</td>
                                            <td>{{ !empty($item['zvitridialy']) ? $item['zvitridialy'] : '' }}</td>
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

        @if (empty($items) && !empty($request['type_device']))
            toastr["error"]("Không có kết quả nào thỏa mãn");
        @endif

        $('.module_search').submit(function(event) {
            event.preventDefault();

            if ($('select[name="type_device"]').val() === '') {
                toastr["error"]("Loại thiết bị không được để trống");
            } else {
                $(this).unbind('submit').submit();
            }
        });
    </script>
@endpush
