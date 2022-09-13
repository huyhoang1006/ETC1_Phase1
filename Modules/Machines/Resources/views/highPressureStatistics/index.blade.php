@extends('layouts.master')
@section('pageTitle', $title)

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard.index') }}">Trang chủ</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="javaScript:void();">Báo cáo thống kê</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="javaScript:void();">Phòng cao áp</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">{{ $title }}</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" action="">
                    <div class="form-group form_input form_date">
                        <p>Ngày bắt đầu</p>
                        <div class="input-group">
                            <input type="date" class="form-control" name="startTime">
                        </div>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Ngày kết thúc</p>
                        <div class="input-group">
                            <input type="date" class="form-control" name="endTime">
                        </div>
                    </div>
                    @if ($type == 'ton-hao-may-bien-ap-phan-phoi')
                        <div class="form-group form_input">
                            <p>Hợp đồng thiết bị</p>
                            <select class="select2-single form-control" name="zContract_Number[]" multiple="multiple">
                                @if (!empty($contracts))
                                    @foreach ($contracts as $contract)
                                        <option value="{{ $contract['id'] }}">{!! $contract['sym'] !!}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @endif
                    <div class="form-group form_input">
                        <p>Hãng</p>
                        <select class="select2-single form-control" name="zManufacturer_ids[]" multiple="multiple">
                            @if (!empty($manufactures))
                                @foreach ($manufactures as $manufacture)
                                    <option value="{{ $manufacture['id'] }}">{!! $manufacture['zsym'] !!}</option>
                                @endforeach
                            @endif
                        </select>
                        <input type="hidden" name="zManufacturer_names">
                    </div>
                    <div class="form-group form_input">
                        <p>Đơn vị quản lý</p>
                        <select class="select2-single form-control" name="zrefnr_dvql_ids[]" multiple="multiple">
                            @if (!empty($units))
                                @foreach ($units as $unit)
                                    <option value="{{ $unit['id'] }}">{!! $unit['zsym'] !!}</option>
                                @endforeach
                            @endif
                        </select>
                        <input type="hidden" name="zrefnr_dvql_names">
                    </div>
                    <div class="form-group form_input" style="display: none;">
                        <p>Tùy chọn thời gian</p>
                        <div class="input-group">
                            <select class="form-control" name="timeType">
                                <option value="3">Năm</option>
                                <option value="2">Quý</option>
                                <option value="1">Tháng</option>
                            </select>
                        </div>
                    </div>
                    @if ($type != 'ton-hao-may-bien-ap-phan-phoi')
                        <div class="form-group form_input">
                            <p>Loại báo cáo</p>
                            <div class="input-group">
                                <select class="form-control" name="reportType">
                                    <option value="{{ route('admin.highPressureStatisticsExactlyTime') }}">Báo cáo thống kê theo khoảng thời gian tùy chọn</option>
                                    <option value="{{ route('admin.highPressureStatisticsQuarterly') }}">Báo cáo theo quý</option>
                                    <option value="{{ route('admin.highPressureStatisticsAnnually') }}">Báo cáo theo năm</option>
                                    <option value="{{ route('admin.highPressureStatisticsSalesAndQuality') }}">Báo cáo theo doanh số và chất lượng từng nhà sản xuất</option>
                                    <option value="{{ route('admin.highPressureStatisticsSalesByManufacture') }}">{{ $type == 'may-bien-ap-phan-phoi' ? 'Báo cáo so sánh doanh số giữa các nhà sản xuất' : 'Báo cáo so sánh số lượng thí nghiệm mẫu giữa các nhà sản xuất' }}</option>
                                    <option value="{{ route('admin.highPressureStatisticsQualityByManufacture') }}">Báo cáo so sánh chất lượng nhà sản xuất</option>
                                    <option value="{{ route('admin.highPressureStatisticsByUnit') }}">{{ $type == 'may-bien-ap-phan-phoi' ? 'Báo cáo theo đơn vị sử dụng' : 'Báo cáo số lượng thí nghiệm mẫu theo đơn vị sử dụng' }}</option>
                                </select>
                            </div>
                        </div>
                    @endif
                    <input type="hidden" name="type" value="{{ $classType }}">
                    <input type="hidden" name="testItemNumber" value="{{ $testItemNumber }}">
                    <input type="hidden" name="directoryName" value="{{ $type }}">
                    <input type="hidden" name="fileTitle" value="{{ $title }}">
                    <div class="form-group form_submit mb-0" style="display: flex;align-items: center;margin-bottom: 15px;">
                        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        <a class="downloadBtn" href="" style="width: 100%;height: 38px;box-shadow: none;padding-left: 0;padding-right: 0;background: #1a4796;color: #fff;display: none;justify-content: center;align-items: center;border-radius: 3px;font-size: 15px;margin-left: 10px;border: 1px solid #141d46;" download>Xuất file</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-12 col-xl-12" style="height: 100vh;position: relative;">
            <iframe id="previewExcel" src='' width='100%' height="100%" frameborder='0'></iframe>
            <div class="loadingBox" style="position: absolute;top: 0;left: 15px;width: calc(100% - 30px);height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('select[name="reportType"]').change(function () {
            if ($(this).val() === "{{ route('admin.highPressureStatisticsOptionalTime') }}") {
                $('select[name="timeType"]').parents('.form_input').show();
            } else {
                $('select[name="timeType"]').val('3').parents('.form_input').hide();
            }
        });

        $('select[name="zManufacturer_ids[]"]').change(function(){
            let names = '',
                count = 0;
            $(this).find('option').each(function(){
                if ($(this).is(':selected')) {
                    names += count === 0 ? $(this).text() : (', ' + $(this).text());
                    count++;
                }
            });
            $('input[name="zManufacturer_names"]').val(names);
        });

        $('select[name="zrefnr_dvql_ids[]"]').change(function(){
            let names = '',
                count = 0;
            $(this).find('option').each(function(){
                if ($(this).is(':selected')) {
                    names += count === 0 ? $(this).text() : (', ' + $(this).text());
                    count++;
                }
            });
            $('input[name="zrefnr_dvql_names"]').val(names);
        });

        $('.module_search').submit(function(e){
            e.preventDefault();
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1'
            });
            $('.dataBox').css({
                'min-height': '160px'
            });

            let url = $(this).find('select[name="reportType"]').val();

            @if ($type == 'ton-hao-may-bien-ap-phan-phoi')
                url = '{{ route('admin.highPressureStatisticsLossQuality') }}';
            @endif


            $.ajax({
                url: url,
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
