@extends('layouts.master')

@section('pageTitle', 'Rơ le - Thống kê rơ le hư hỏng')

@push('css')
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            white-space: break-spaces;
        }
    </style>
@endpush

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="">Phòng hóa dầu</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div>
        <div class="row dashboard-index detail-report" style="padding-top: 0;">
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="card-body module_search" style="padding-top: 15px;padding-bottom: 20px;">
                            <div class="form-group form_input form_date">
                                <p>Thời gian bắt đầu cần thống kê</p>
                                <input type="date" class="form-control date-from" name="from" value="{{ request()->get('from') }}" />
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Thời gian kết thúc cần thống kê</p>
                                <input type="date" class="form-control date-to" name="to" value="{{ request()->get('to') }}" />
                            </div>
                            <div class="form-group form_input">
                                <p>Đơn vị quản lý</p>
                                <input type="text" class="form-control data-management" name="zrefnr_dvql"/>
                            </div>
                            <div class="form-group form_input">
                                <p>Thiết bị</p>
                                <input type="text" class="form-control" name="device">
                            </div>
                            <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 20%;">
                                <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                                <a class="downloadBtn" href="" style="width: 100%;height: 38px;box-shadow: none;padding-left: 0;padding-right: 0;background: #1a4796;color: #fff;display: none;justify-content: center;align-items: center;border-radius: 3px;font-size: 15px;margin-left: 10px;border: 1px solid #141d46;" download>Xuất file</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-xl-12" style="height: 100vh;position: relative;">
                <iframe id="previewExcel" src='' width='100%' height="100%" frameborder='0'></iframe>
                <div class="loadingBox" style="position: absolute;top: 0;left: 15px;width: calc(100% - 30px);height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('.module_search').submit(function(e){
            e.preventDefault();
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1'
            });
            $.ajax({
                url: '{{ route('admin.sf6GasQualityReportExport') }}',
                type: 'POST',
                dataType: 'html',
                data: $(this).serialize()
            }).done(function(result) {
                $('.loadingBox').css({
                    'opacity': '0',
                    'z-index': '-1'
                });
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                    $('.downloadBtn').attr('href', response['url']).css({
                        'display': 'none'
                    });
                }

                if (typeof response['success'] !== 'undefined') {
                    $('#previewExcel').attr('src', 'https://view.officeapps.live.com/op/embed.aspx?src=' + response['url'] + '?t={{ time() }}');
                    $('.downloadBtn').attr('href', response['url']).css({
                        'display': 'flex'
                    });
                }
            });
        });
    </script>
@endpush
