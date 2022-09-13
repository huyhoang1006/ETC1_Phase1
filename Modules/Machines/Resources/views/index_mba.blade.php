@extends('layouts.master')
@section('pageTitle', 'Trang chủ - Báo cáo phân tích - Phòng cao áp - Máy biến áp')

@php
    $id = '';
    if( session()->has('id') ){
        $id = session()->get('id');
    }
@endphp

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo phân tích</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Phòng cao áp</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Máy biến áp</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px;position: relative;">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Quản lý báo cáo máy biến áp phòng cao áp</h4>
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
                                @foreach ($areas as $area)
                                    <option value="{{ $area['id'] }}" {{ request()->get('area') == $area['id'] ? 'selected' : '' }}>{{ $area['zsym'] }}</option>
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
                            <p>Ngăn lộ/hệ thống</p>
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
                                @foreach ($manufactures as $key => $manufacture)
                                    <option value="{!! $key !!}" {{ request()->get('manufacture_id') == $key ? 'selected' : '' }}>{!! $manufacture !!}</option>
                                @endforeach
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
                                @for($i = date('Y'); $i >= 1970; $i--)
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
                            <p>Công suất</p>
                            <div class="input-group">
                                <input type="text" id="zcapacity" class="form-control" name="zcapacity" value="{{ request()->get('zcapacity') }}"/>
                            </div>
                        </div>
                        <div class="form-group form_input">
                            <p>Tổ đấu dây</p>
                            <div class="input-group">
                                <input type="text" id="ztodauday" class="form-control" name="ztodauday" value="{{ request()->get('ztodauday') }}"/>
                            </div>
                        </div>
                        <div class="form-group form_input">
                            <p>Điện áp định mức</p>
                            <div class="input-group">
                                <input type="text" id="zdienapdinhmuc" class="form-control" name="zdienapdinhmuc" value="{{ request()->get('zdienapdinhmuc') }}"/>
                            </div>
                        </div>
                        <div class="form-group form_submit mb-0">
                            <button type="submit" class="btn btn-dark" id="btn-submit">Tìm kiếm</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card m-b-30" id="block-report" style="display:none;">
                <div class="card-body module_search v-right" style=" padding-top: 20px;padding-bottom: 20px;">
                    <div class="form-group form_input mb-0">
                        <select class="select2-single form-control" name="state" id="ViewReport">
                            <option value="">Chọn loại báo cáo</option>
                            <option value="{{ route('admin.highPressure.transformers.overviewCheckReport') }}?title=">1. Kiểm tra tổng quan</option>
                            <option value="{{ route('admin.currentAndNoLoadLossReport') }}?title=">2. Báo cáo phân tích kết quả thí nghiệm dòng điện và tổn hao không tải ở điện áp thấp và điện trở cách điện</option>
                            <option value="{{ route('admin.syllableWordCircuitReport') }}?title=">3. Báo cáo phân tích kết quả thí nghiệm điện trở cách điện gông từ, mạch từ</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?title=to-dau-day">4. Báo cáo tổ đấu dây</option>
                            <option value="{{ route('admin.reportPorcelainTest') }}?title=">5. Báo cáo phân tích kết quả thí nghiệm các sứ đầu vào</option>
                            <option value="{{ route('admin.oneWayResistorReport') }}?title=">6. Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây</option>
                            <option value="{{ route('admin.rateOfChangeReport') }}?title=">7. Báo cáo phân tích kết quả thí nghiệm tỉ số biến đổi </option>
                            <option value="{{ route('admin.dielectricLossReport') }}?title=">8. Báo cáo phân tích kết quả thí nghiệm tổn hao điện môi và điện dung các cuộn dây máy biến áp</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?title=tro-khang-ro">9. Báo cáo trở kháng rò</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?title=dac-tinh-dap-ung-tan-so-quet">10. Báo cáo đo đặc tính đáp ứng tần số quét</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?title=do-ham-luong-am-trong-cach-dien-ran">11. Báo cáo Đo hàm lượng ẩm trong cách điện rắn</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=do-phong-dien-cuc-bo-online">12. Báo cáo Đo phóng điện cục bộ online</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=do-phong-dien-cuc-bo-offline">13. Báo cáo Đo phóng điện cục bộ offline</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=thi-nghiem-bo-dieu-ap-duoi-tai">14. Báo cáo Thí nghiệm bộ điều áp dưới tải</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=thi-nghiem-dien-ap-xoay-chieu-tang-cao">15. Báo cáo Thí nghiệm điện áp xoay chiều tăng cao</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=thi-nghiem-dien-ap-ac-cam-ung">16. Báo cáo Thí nghiệm điện áp AC cảm ứng</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=do-ton-that-khong-tai-va-dong-dien-khong-tai-o-dien-ap-dinh-muc">17. Báo cáo Đo tổn thất không tải và dòng điện không tải ở điện áp định mức</option>
                            <option value="{{ route('admin.writeDataShareReportTransformers') }}?&title=do-ton-that-co-tai-va-dien-ap-ngan-mach">18. Báo cáo Đo tổn thất có tải và điện áp ngắn mạch</option>
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0">
                        <button id="btnViewReport" type="button" class="btn btn-dark" onclick="gotoReport()">Xem báo cáo</button>
                    </div>
                </div>
            </div>
            <div class="card m-b-30">
                <div class="card-body" id="card-body">
                    <div class="table-responsive" id="table-devices">
                        @include('machines::data-device')
                    </div>
                    <div class="table-responsive" id="table-reports">
                        <div class="col-12">
                            <button class="btn btn-default back-to-device">Quay lại bảng thiết bị</button>
                        </div>
                        <div class="col-12">
                            <p style="text-align: center;" id="name_device"></p>
                        </div>
                        <div class="wrapper-reports">
                        </div>
                    </div>
                </div>
                <div class="loadingBox" style="top: 0;left: 15px;width: calc(100% - 30px);height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;position: absolute; left: 50%;">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="errors" value="{{ json_encode($errors->all()) }}">
    <input type="hidden" id="device_id">
@endsection
@push('scripts')
    <script>
        $(function(){
            let errors = JSON.parse($('#errors').val());
            let count = localStorage.getItem('back') ?? 0;
            if(errors.length > 0 && count % 2 == 0 ){
                for (let i = 0; i < errors.length; i++) {
                    toastr["error"](errors[i]);
                }
                localStorage.setItem("back", ++count);
            }
        });
        $(function(){
            $("input:checkbox").on('click', function() {
                var $box = $(this);
                if ($box.is(":checked")) {
                    var group = "input:checkbox[name='" + $box.attr("name") + "']";
                    $(group).prop("checked", false);
                    $box.prop("checked", true);
                } else {
                    $box.prop("checked", false);
                }
            });
        });

        function gotoReport() {
            let viewContent = document.getElementById('ViewReport').value;
            let ids = [];
            let viewchecks = $('#viewCheck[type=checkbox]:checked').each(function(e){
                ids.push($(this).val());
            });

            let totalChecked = $('input[type=radio]:checked').length;
            // check select report
            if( !viewContent ){
                toastr["error"]('Vui lòng chọn loại báo cáo!');
                return;
            }
            // check selected report
            if( ids.length == 0 ){
                toastr["error"]('Vui lòng chọn biên bản để xuất báo cáo!');
                return;
            }
            const request = $('#form').serialize();
            const url = viewContent + '&ids=' + ids.join(',')+`&${request}`;
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1',
                'position': 'absolute',
            });
            $.ajax({
                url: "{{ route('admin.ajaxValidateReportOneTypeOfRecord') }}",
                type: 'POST',
                dataType: 'JSON',
                data: { ids }
            }).done(function(result) {
                $('.loadingBox').css({
                    'opacity': '0',
                    'z-index': '-1',
                    'position': 'unset'
                });
                console.log(result['error']);
                if (typeof result['error'] !== 'undefined') {
                    for (let i = 0; i < result['error'].length; i++) {
                        toastr["error"](result['error'][i]);
                    }
                }

                if (typeof result['success'] !== 'undefined') {
                    let count = localStorage.getItem('back') ?? 0;
                    if( count ){
                        localStorage.setItem("back", ++count);
                    }
                    window.location.href = url;
                }
            });
        }
        // ajax get report by device id
        function ajaxGetReports(id){

            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1',
                'position': 'absolute',
            });
            $('.loadingBox img').css({
                'top' : window.pageYOffset - 450,
            });

            const url = '{{ route('admin.ajaxGetReport') }}';
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'html',
                data: { id, startDate, endDate }
            }).done(function(result) {
                $('.loadingBox').css({
                    'opacity': '0',
                    'z-index': '-1',
                    'position': 'unset'
                });
                let response = JSON.parse(result);
                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }

                if (typeof response['success'] !== 'undefined') {
                    const data = response['html'];
                    $('.wrapper-reports').html(data);
                    $('#table-devices').hide();
                    $('#table-reports').show();
                    $('#block-report').show();
                }
            });
        }
        // ajax get device form request
        function ajaxGetDevice(request){
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1',
                'position': 'absolute',
            });
            $('.loadingBox img').css({
                'top' : '0',
            });

            const url = '{{ route('admin.ajaxGetDevice') }}';
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'html',
                data: request
            }).done(function(result) {
                $('.loadingBox').css({
                    'opacity': '0',
                    'z-index': '-1',
                    'position': 'unset'
                });
                let response = JSON.parse(result);
                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                    $('#table-devices tbody').empty();
                }

                if (typeof response['success'] !== 'undefined') {
                    $('#table-devices').html(response['html']);
                    $('#table-devices').show();
                }
            });
        }
        // call ajax get report by device
        $("body").on("click", ".filter-report", function(e){
            e.preventDefault();
            const id = $(this).data('id');
            $('#device_id').val(id);
            $('#name_device').text(`Thiết bị: ${$(this).text()}`);
            ajaxGetReports(id);
        });
        // btn show/hide table device/report
        $('.back-to-device').on('click', function(){
            $('#table-reports').hide();
            $('#table-devices').show();
            $('#block-report').hide();
        });
        // hanlde submit form
        $('#btn-submit').click(function(e){
            e.preventDefault();
            const eleReport = $('#table-reports');
            const eleDevice = $('#table-devices');
            const id = $('#device_id').val();
            if( eleReport.is(':visible') ){
                ajaxGetReports(id);
                return;
            }
            if( eleDevice.is(':visible') ){
                ajaxGetDevice($( '#form' ).serialize());
                return;
            }
        });
    </script>
@endpush
