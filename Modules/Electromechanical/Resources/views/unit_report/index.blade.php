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
            <h4 class="m-b-30">Báo cáo thống kê cáp và dây dẫn đã thí nghiệm - Báo cáo theo đơn vị sử dụng - Bảng số liệu tổng hợp</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style="padding-top: 15px;padding-bottom: 20px;">
                    @csrf
                    <div class="form-group form_input form_date">
                        <p>Năm bắt đầu cần thống kê</p>
                        <select class="select2-single form-control year-from" name="year_from">
                            @for($i=$year; $i >= 1900;$i -= 1)
                                <option value="{{$i}}">{{$i}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Năm kết thúc cần thống kê</p>
                        <select class="select2-single form-control year-to" name="year_to">
                            @for($i=$year; $i >= 1900;$i -= 1)
                                <option value="{{$i}}">{{$i}}</option>
                            @endfor
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
                var year_from = $('.year-from').val();
                var year_to = $('.year-to').val();
                $('.loadingBox').css({
                    'opacity': '.5',
                    'z-index': '1'
                });
                $.ajax({
                    type: "post",
                    url: "{{route('admin.exportPerUnitReport')}}",
                    data: {_token:token, year_from:year_from, year_to:year_to},
                    dataType: "JSON",
                    success: function (data) {
                        if (data == 0) {
                            toastr["error"]('Không tìm thấy kết quả nào thỏa mãn');
                            $('.button-download').css({"display":"none"});
                        }else{
                            $('.button-download').css({"display":"inherit"});
                            var link_preview = 'https://view.officeapps.live.com/op/embed.aspx?src='+data;
                            $('.preview-file').attr('src', link_preview);
                            var a = document.getElementById('download-file'); 
                            a.href = data;
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
