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
            <h4 class="m-b-30">Báo cáo thống kê công tác thí nghiệm</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.exportExperimentalStatisticsReport') }}">
                    @csrf
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu cần thống kê</p>
                        <input type="date" class="form-control date-from" name="from"/>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc cần thống kê</p>
                        <input type="date" class="form-control date-to" name="to"/>
                    </div>
                    <div class="form-group form_input">
                        <p>Loại hình thí nghiệm</p>
                        <select class="select2-single form-control" name="ztestType_ids[]" id="ztestType_ids" multiple="multiple">
                            @foreach ($ztestTypes as $type)
                            <option value="{{ $type['id'] }}">{!! $type['zsym'] !!}</option>                                
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 250px;">
                        <button type="button" class="btn btn-dark btn-export__dl5">Tìm kiếm</button>
                        <button type="submit" class="btn btn-dark button-download" style="display: none;margin-left: 20px;">Xuất file</button>
                    </div>
                </form>
            </div>
            <div class="card dataBox" style="background: none;box-shadow: none;">
                <div class="card-body min_table list_list" style="display: none;">
                    <div style="overflow-x: scroll">
                        <div class="table_minwidth" id="dataTable">
                        </div>
                    </div>
                </div>
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
                var ztestType_ids = $('#ztestType_ids').val();
                $('.loadingBox').css({
                    'opacity': '.5',
                    'z-index': '1'
                });
                $.ajax({
                    type: "post",
                    url: "{{route('admin.previewExperimentalStatisticsReport')}}",
                    data: {
                        _token:token, 
                        from, 
                        to, 
                        ztestType_ids
                    },
                    dataType: "JSON",
                    success: function (data) {
                        if (data['error']) {
                            toastr["error"]('Không tìm thấy kết quả nào thỏa mãn');
                            $('.button-download').css({"display":"none"});
                            $('.dataBox .min_table').css({
                                'display': 'none'
                            });
                        }else{
                            $('.dataBox').css({
                                'height': 'auto',
                                'background': '#fff',
                                'box-shadow': '0 10px 30px 0 rgb(24 28 33 / 5%)'
                            });
                            $('.button-download').css({"display":"block"});
                            $('#dataTable').html(data['html']);
                            $('.dataBox .min_table').css({
                                'display': 'block'
                            });
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
