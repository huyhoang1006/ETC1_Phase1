@extends('layouts.master')
@section('pageTitle', 'QUẢN LÝ THIẾT BỊ')

@php
    $ids = [];
    if( session()->has('ids') ){
        $ids = session()->get('ids');
    }
@endphp

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Phòng cao áp</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Máy cắt</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="row dashboard-index detail-report" style="padding-top: 0px;position: relative;">
    <div class="col-lg-12 col-xl-12">
        <h4 class="m-b-30">Quản lý báo cáo máy cắt phòng cao áp</h4>
    </div>
    <div class="col-sm-12 col-xl-12">
        <div class="card m-b-30">
            <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
            </div>
            <form method="GET" id="form">
                <div class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;">
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu thí nghiệm</p>
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control" name="start_date" value="{{ request()->get('start_date') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc thí nghiệm</p>
                        <div class="input-group">
                            <input type="date" id="end_date" class="form-control" name="end_date" value="{{ request()->get('end_date') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Khu vực</p>
                        <select  class="select2-single form-control" id="area" name="area">
                            <option value="">-- Khu vực --</option>
                            @foreach ($areas as $id => $area)
                                <option value="{{ $id }}" {{ request()->get('area') == $id ? 'selected' : '' }}>{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Trạm/ Nhà máy</p>
                        <div class="input-group">
                            <input type="text" id="td" class="form-control" name="td" value="{{ request()->get('td') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Ngăn lộ/ Hệ thống</p>
                        <div class="input-group">
                            <input type="text" id="nl" class="form-control" name="nl" value="{{ request()->get('nl') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Thiết bị</p>
                        <div class="input-group">
                            <input type="text" id="device" class="form-control" name="device" value="{{ request()->get('device') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Hãng sản xuất</p>
                        <select class="select2-single form-control" id="manufacture" name="manufacture_id">
                            <option value="">-- Hãng sản xuất --</option>
                            @if (!empty($manufactures))
                                @foreach ($manufactures as $key => $manufacture)
                                    <option value="{!! $key !!}" {{ request()->get('manufacture_id') == $key ? 'selected' : '' }}>{!! $manufacture !!}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Kiểu</p>
                        <div class="input-group">
                            <input type="text" id="type" class="form-control" name="type" value="{{ request()->get('type') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Số chế tạo</p>
                        <div class="input-group">
                            <input type="text" id="serial_number" class="form-control" name="serial_number" value="{{ request()->get('serial_number') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Năm sản xuất</p>
                        <select class="select2-single form-control" id="zYear_of_Manafacture" name="zYear_of_Manafacture">
                            <option value="">-- Năm sản xuất --</option>
                            @for($i = date('Y'); $i >= 2000; $i--)
                            <option value="{{ $i }}" {{ request()->get('zYear_of_Manafacture') == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Nước sản xuất</p>
                        <div class="input-group">
                            <input type="text" id="country" class="form-control" name="country" value="{{ request()->get('country') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Dòng điện định mức</p>
                        <div class="input-group">
                            <input type="text" id="zdongdiendinhmuc" class="form-control" name="zdongdiendinhmuc" value="{{ request()->get('zdongdiendinhmuc') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Điện áp định mức</p>
                        <div class="input-group">
                            <input type="text" id="zdienapdinhmuc" class="form-control" name="zdienapdinhmuc" value="{{ request()->get('zdienapdinhmuc') }}"/>
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Loại hình thí Nghiệm</p>
                        <select class="select2-single form-control" name="ztestType[]" id="ztestType" multiple>
                            @foreach($typeOfExperiments as $val)
                                <option value="{{ $val }}" {{ in_array($val, request()->get('ztestType') ?? []) ? 'selected' : '' }}>{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Chủng loại</p>
                        <select  class="select2-single form-control" name="deviceType" id="deviceType">
                            <option value="{{ config('constant.device_mc.3_bo') }}" {{ request()->get('deviceType') == config('constant.device_mc.3_bo') ? 'selected' : '' }}>Máy cắt 3 pha 1 bộ truyền động</option>
                            <option value="{{ config('constant.device_mc.1_bo_1_buong') }}" {{ request()->get('deviceType') == config('constant.device_mc.1_bo_1_buong') ? 'selected' : '' }}>Máy cắt 1 pha 1 bộ truyền động 1 buồng cắt</option>
                            <option value="{{ config('constant.device_mc.1_bo_2_buong') }}" {{ request()->get('deviceType') == config('constant.device_mc.1_bo_2_buong') ? 'selected' : '' }}>Máy cắt 1 pha 1 bộ truyền động 2 buồng cắt</option>
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0">
                        <button id="btn-submit" type="submit" class="btn btn-dark">Tìm kiếm</button>
                    </div>
                </div>
            </form>
        </div>
        @yield('content2')
        <div class="data-item">
            @foreach ($items as $item)
                @if ( !empty(@$item['class.type']) )
                    @foreach ($item['class.type'] as $class)
                        <div class="{{ @$class['id'] }}" data-phase="{{@$class['id']}}"></div>
                    @endforeach
                @endif
            @endforeach
        </div>
        <div class="card m-b-30">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="thead-blue">
                            <tr>
                                <th></th>
                                <th>Thiết bị</th>
                                <th>Biên bản</th>
                                <th>Ngày thí nghiệm</th>
                                <th>Group máy cắt</th>
                                <th>Pha</th>
                                <th>Khu vực</th>
                                <th>Trạm/ Nhà máy</th>
                                <th>Ngăn lộ/ hệ thống</th>
                                <th>Hãng sản xuất</th>
                                <th>Kiểu</th>
                                <th>Số chế tạo</th>
                                <th>Năm sản xuất</th>
                                <th>Nước sản xuất</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                            <tr style="background-color: #f2f3f7;">
                                <td>
                                    {{-- <input  id="viewCheck" value="{{ @$item['id'] }}" type="checkbox" class="mr-1"> --}}
                                </td>
                                <td>{{ @$item['name'] }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>{{ @$item['zArea.zsym'] }}</td>
                                <td>{{ @$item['zrefnr_td.zsym'] }}</td>
                                <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
                                <td>{{ @$item['zManufacturer.zsym'] }}</td>
                                <td>{{ @$item['zCI_Device_Kind.zsym'] }}</td>
                                <td>{{ @$item['serial_number'] }}</td>
                                <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                                <td>{{ @$item['zCountry.name'] }}</td>
                            </tr>
                            @if ( !empty(@$item['class.type']) )
                                @foreach ($item['class.type'] as $class)
                                <tr>
                                    <td>
                                        <input id="viewCheck" value="{{ @$class['id'] }}"
                                        data-device = "{{ @$item['id'] }}" type="checkbox" class="mr-1"
                                        data-date="{{ date('d/m/Y', @$class['zlaboratoryDate']) }}"
                                        {{ in_array(@$class['id'], $ids) ? 'checked' : '' }}>
                                    </td>
                                    <td></td>
                                    <td>{{ @$class['name'] }}</td>
                                    <td>{{ !empty($class['zlaboratoryDate']) ? date('d/m/Y', $class['zlaboratoryDate']) : '' }}</td>
                                    <td>{{ @$item['group'] }}</td>
                                    <td>{{ @$class['zphase.zsym'] }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @endforeach
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12 col-xl-12 overlay hidden">
        <div class="loadingBox">
            <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script>
        $('#btn-submit').on('click', function(e) {
            e.preventDefault();
            const area = $('#area').val();
            const td = $('#td').val();
            const nl = $('#nl').val();
            const device = $('#device').val();
            const manufacture = $('#manufacture').val();
            const type = $('#type').val();
            const serial_number = $('#serial_number').val();
            const zYear_of_Manafacture = $('#zYear_of_Manafacture').val();
            const country = $('#country').val();
            const zdongdiendinhmuc = $('#zdongdiendinhmuc').val();
            const zdienapdinhmuc = $('#zdienapdinhmuc').val();
            const ztestType = $('#ztestType').val();
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val();
            if( !area && !td && !nl && !device && !manufacture && !type && !serial_number && !zYear_of_Manafacture && !country && !zdongdiendinhmuc && !zdienapdinhmuc && ztestType.length == 0 && !start_date && !end_date){
                toastr["error"]("Vui lòng nhập ít nhất 1 trường để tìm kiếm thiết bị!");
            } else {
                $('#form').submit();
            }
        });
    </script>
@endpush