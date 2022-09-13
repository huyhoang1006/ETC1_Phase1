@extends('layouts.master')

@section('pageTitle', 'QUẢN LÝ THIẾT BỊ')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý thiết bị</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div>
        <div class="row align-items-center m-b-20">
            <div class="col-md-8 col-lg-8">
                <h4 class="page-title">Danh sách thiết bị thí nghiệm hiệu chỉnh</h4>
            </div>
        </div>
        <div class="row dashboard-index detail-report">
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="card-body module_search" method="get" action="{{ route('admin.device.index') }}" id="search-form">
                            <input type="hidden" name="nl" value="{{ request()->get('nl') }}">
                            <div class="form-group form_input">
                                <input type="text" class="form-control" name="name" value="{{ request()->get('name') }}" id="inputText" placeholder="Tìm kiếm theo tên">
                            </div>
                            <div class="form-group form_input">
                                <input type="text" class="form-control" name="series" value="{{ request()->get('series') }}" id="inputText" placeholder="Tìm kiếm theo Serial">
                            </div>
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="manufacture">
                                    <option value="">Hãng sản xuất</option>
                                    @foreach($manufactures as $manufacture)
                                        <option {{ request()->get('manufacture') == $manufacture['zsym'] ? 'selected' : '' }} value="{!! $manufacture['zsym'] !!}">{!! $manufacture['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="year">
                                    <option value="">Năm sản xuất</option>
                                    @foreach($years as $year)
                                        <option {{ request()->get('year') == $year['id'] ? 'selected' : '' }} value="{{ $year['id'] }}">{{ $year['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ( !request()->get('nl') && !request()->get('td') && !request()->get('dvql'))
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="city">
                                    <option value="">Khu vực</option>
                                    @foreach($cities as $city)
                                        <option {{ request()->get('city') == $city['id'] ? 'selected' : '' }} value="{{ $city['id'] }}">{{ $city['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="dvql_form">
                                    <option value="">Đơn vị quản lý</option>
                                    @foreach($dvqls as $dvql)
                                        <option {{ request()->get('dvql_form') == $dvql['id'] ? 'selected' : '' }} value="{{ $dvql['id'] }}">{!! $dvql['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="td_form">
                                    <option value="">Trạm/Nhà máy</option>
                                    @foreach($tds as $td)
                                        <option {{ request()->get('td_form') == $td['id'] ? 'selected' : '' }} value="{{ $td['id'] }}">{{ $td['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_type">
                                <select class="select2-single form-control" name="nl_form">
                                    <option value="">Ngăn lộ/Hệ thống</option>
                                    @foreach($nls as $nl)
                                        <option {{ request()->get('nl_form') == $nl['id'] ? 'selected' : '' }} value="{{ $nl['id'] }}">{{ $nl['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group form_submit">
                                <button type="submit" class="btn btn-dark" id="btn-submit">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                    <div class="wrapper-device">
                        @include('device::data')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="errors" value="{{ json_encode($errors->all()) }}">
@endsection

@push('scripts')
    <script>
        $(function(){
            let errors = JSON.parse($('#errors').val());
            if(errors.length > 0){
                for (let i = 0; i < errors.length; i++) {
                    toastr["error"](errors[i]);
                }
            }
        });
        if (typeof activeMenu !== 'undefined') {
            let a = window.location.href;
            let arr = a.split("&");
            a = arr[0];
            for (let abc = $(".vertical-menu a").filter(function() {
                return a === this.href;
            }).addClass("active-current").parent().addClass("active"); ;) {
                if (!abc.is("li")) break;
                abc.parent().css({"display": "block"});
                abc = abc.parent().addClass("in").parent().addClass("active");
            }
        }
    </script>
@endpush
