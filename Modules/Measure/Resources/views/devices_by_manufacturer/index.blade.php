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
            <h4 class="m-b-30">Báo cáo số lượng thiết bị sai số không đạt theo hãng sản xuất</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.defectiveDevicesByManufacturer') }}">
                    @csrf
                    <div class="form-group form_input form_date">
                        <p>Đơn vị quản lý</p>
                        <select class="select2-single form-control" name="zrefnr_dvql" id="zrefnr_dvql">
                            <option value="">Chọn đơn vị quản lý</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit['zsym'] }}" {{ request()->get('zrefnr_dvql') == $unit['zsym'] ? 'selected' : '' }}>{!! $unit['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Khu vực (Tỉnh/ thành phố)</p>
                        <select class="select2-single form-control" name="areas" id="areas">
                            <option value="">Khu vực (Tỉnh/ thành phố)</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area['zsym'] }}">{!! $area['zsym'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu cần thống kê</p>
                        <input type="date" class="form-control date-from" name="from" value="{{ request()->get('from') }}" />
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc cần thống kê</p>
                        <input type="date" class="form-control date-to" name="to" value="{{ request()->get('to') }}" />
                    </div>
                    <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 300px;margin-top: 12px;">
                        <button type="button" class="btn btn-dark btn-export__dl5">Xem báo cáo</button>
                    </div>
                </form>
            </div>
            <a id="download-file" href="#" style="padding: 15px;display: none;">
                <button style="width: 100px;" type="button" data-toggle="tooltip" data-placement="top" title="Xuất file .xlsx" class="float-right btn btn-success">
                    <i class="fas fa-download"></i>
                    Tải về
                </button>
            </a>
            <div class="col-lg-12 col-xl-12" style="height: 100vh;">
                <iframe class="preview-file" src="" width="100%" height="100%" frameborder="0"></iframe>
                <div class="loadingBox" style="position: absolute;top: 0;left: 15px;width: calc(100% - 30px);height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <style type="text/css">

        /* Loading full page */
        #loading_box {
            position: fixed;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,.5);
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            z-index: 100000;
            opacity: 0;
            visibility: hidden;
        }
        #loading_image {
            width: 100%;
            height: 100%;
            background: url('/theme/assets/images/unnamed.gif') no-repeat center center;
            background-size: 100px;
        }
        /* loading in box */
        .loading {
            position: relative;
            transition: 0.2s;
        }
        .loading:before, .loading:after{
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            z-index: 100000;
        }
        .loading:before{
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        .loading:after{
            background: url('/assets/img/unnamed.gif') no-repeat center center;
            background-size: 100px;
        }
    </style>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ready(function () {
            $(".btn-export__dl5").on("click", function() {
                var token = $("input[name='_token']").val();
                var date_from = $('.date-from').val();
                var date_to = $('.date-to').val();
                var data_management = $('#zrefnr_dvql').val();
                var data_area = $('#areas').val();
                $('.loadingBox').css({
                    'opacity': '.5',
                    'z-index': '1'
                });
                $.ajax({
                    type: "post",
                    url: "{{route('admin.exportDefectiveDevicesByManufacturer')}}",
                    data: {_token:token, date_from:date_from, date_to:date_to, data_management:data_management, data_area:data_area},
                    dataType: "JSON",
                    success: function (data) {
                        var link_preview = 'https://view.officeapps.live.com/op/embed.aspx?src='+data;
                        $('.preview-file').attr('src', link_preview);
                        var a = document.getElementById('download-file');
                        a.href = data;
                        $('#download-file').css({"display":"block"});
                        $('.loadingBox').css({
                            'opacity': '0',
                            'z-index': '-1'
                        });
                    },
                    error: function(error){
                        $('.loadingBox').css({
                            'opacity': '0',
                            'z-index': '-1'
                        });
                    }
                });
            });
        });
    </script>
@endpush
