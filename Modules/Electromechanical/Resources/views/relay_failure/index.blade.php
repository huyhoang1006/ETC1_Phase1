@extends('layouts.master')
@section('pageTitle', 'BÁO CÁO THỐNG KÊ PHÂN XƯỞNG CƠ ĐIỆN')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="">Phòng phân xưởng cơ điện</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Báo cáo thống kê hệ thống bảo vệ các ngăn lộ trung áp - Báo cáo thống kê hư hỏng rơle</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style="padding-top: 15px;padding-bottom: 20px;">
                    @csrf
                    <div class="form-group form_input form_date">
                        <p>Thời gian bắt đầu cần thống kê</p>
                        <input type="date" class="form-control date-from" name="from" value="{{ request()->get('from') }}" />
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Thời gian kết thúc cần thống kê</p>
                        <input type="date" class="form-control date-to" name="to" value="{{ request()->get('to') }}" />
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Loại thiết bị</p>
                        <select class="select2-single form-control" name="devices[]" multiple="multiple" id="devices">
                            @foreach($types as $type)
                                <option value="{{ $type['id'] }}">{!! $type['type'] !!}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Chủng loại</p>
                        <select class="select2-single form-control" name="type[]" multiple="multiple" id="multiple">
                            @foreach($allDeviceTypes as $val)
                                <option value="{{ $val }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 250px;">
                        <button type="button" class="btn btn-dark btn-export__dl5">Tìm kiếm</button>
                        <a id="download-file" href="#" style="padding: 15px">
                            <button style="width: 100px; display: none;" type="button" title="Xuất file .xlsx" class="btn btn-dark button-download">
                                Xuất file
                            </button>
                        </a>
                    </div>
                </form>
            </div>
            <div class="col-lg-12 col-xl-12" style="height: 100vh;position: relative;">
                <iframe class="preview-file" src="" width="100%" height="100%" frameborder="0"></iframe>
                <div class="loadingBox" style="position: absolute;top: 0;left: 15px;width: calc(100% - 30px);height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
                </div>
            </div>
        </div>
    </div>
    <div id="loading_box"><div id="loading_image"></div></div>
@endsection
@push('scripts')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ready(function () {
            $(".btn-export__dl5").on("click", function() {
                var token = $("input[name='_token']").val();
                var from = $('.date-from').val();
                var to = $('.date-to').val();
                const devices = $('#devices').val();
                var key = 0;
                var filter_type=[];
                $('#multiple :selected').each(function(){
                    filter_type[key++]=$(this).val();
                });
                $('.loadingBox').css({
                    'opacity': '.5',
                    'z-index': '1'
                });
                $.ajax({
                    type: "post",
                    url: "{{route('admin.exportReportRelayFailureStatistics')}}",
                    data: {
                        _token:token, 
                        from, 
                        to, 
                        filter_type, 
                        devices
                    },
                    dataType: "JSON",
                    success: function (data) {
                        if(data['error'] != ''){
                            toastr["error"](data['error']);
                            $('.button-download').css({"display":"none"});
                        }else{
                            $('.button-download').css({"display":"inherit"});
                            var link_preview = 'https://view.officeapps.live.com/op/embed.aspx?src='+data['link'];
                            $('.preview-file').attr('src', link_preview);
                            var a = document.getElementById('download-file');
                            a.href = data['link'];
                            $('.box-iframe').css({"display":"grid"});
                        }
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
