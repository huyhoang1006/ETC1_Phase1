@extends('layouts.master')

@section('pageTitle', 'Trang chủ - Báo cáo thống kê - Phòng cao áp - Báo cáo thống kê số lượng và danh sách thiết bị')

@push('css')
    <style>
        .select2-container .select2-selection--multiple {
            border: none!important;
            min-height: 36px;
        }

        .select2-container .select2-search--inline .select2-search__field {
            line-height: 36px;
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
                        <li class="breadcrumb-item active"><a href="">Phòng cao áp</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div>
        <div class="row dashboard-index detail-report" style="padding-top: 0;">
            <div class="col-lg-12 col-xl-12">
                <h4 class="m-b-30">Báo cáo thống kê số lượng và danh sách thiết bị</h4>
            </div>
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="form card-body module_search" style="align-items: flex-end">
                            <div class="form-group form_input">
                                <p>Thiết bị</p>
                                <select class="select2-single form-control" id="devices" name="devices[]" multiple>
                                    @foreach ($devices as $id => $device)
                                        <option value="{{ $id }}">{{ $device }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Chủng loại</p>
                                <select class="select2-single form-control" name="deviceTypes[]" id="deviceType" multiple="multiple"></select>
                            </div>
                            <div class="form-group form_input">
                                <p>Khu vực</p>
                                <select class="select2-single form-control" name="area">
                                    <option value="">Khu vực</option>
                                    @foreach ($areas as $area)
                                     <option value="{{ $area['id'] }}">{{ $area['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Tình trạng thí nghiệm</p>
                                <select class="select2-single form-control" name="zStage">
                                    <option value="">Tình trạng thí nghiệm</option>
                                    @foreach ($zStage as $zSta)
                                        <option value="{{ $zSta['id'] }}">{{ $zSta['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Đơn vị quản lý</p>
                                <select class="select2-single form-control" name="dvqls[]" multiple="multiple">
                                    @foreach ($dvqls as $dvql)
                                        <option value="{{ $dvql['id'] }}">{!! $dvql['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Hãng sản xuất</p>
                                <select class="select2-single form-control" name="manufacture_ids[]" multiple="multiple"></select>
                            </div>
                            <div class="form-group form_input">
                                <p>Kiểu thiết bị</p>
                                <input type="text"  class="form-control" name="deviceKind" />
                            </div>
                            <div class="form-group form_input">
                                <p>Công suất</p>
                                <input type="text"  class="form-control" name="zcapacity" />
                            </div>
                            <div class="form-group form_input">
                                <p>Tổ đấu dây</p>
                                <input type="text"  class="form-control" name="ztodauday" />
                            </div>
                            <div class="form-group form_input">
                                <p>Điện áp định mức</p>
                                <input type="text"  class="form-control" name="zdienapdinhmuc" />
                            </div>
                            <div class="form-group form_input">
                                <p>Dòng điện định mức</p>
                                <input type="text"  class="form-control" name="zdongdiendinhmuc" />
                            </div>
                            <div class="form-group form_input">
                                <p>Số chế tạo</p>
                                <input type="text"  class="form-control" name="serial_number" />
                            </div>
                            <div class="form-group form_input">
                                <p>Năm sản xuất (Bắt đầu)</p>
                                <select class="select2-single form-control" name="startYear">
                                    <option value="">Năm bắt đầu</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year['id'] }}">{{ $year['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Năm sản xuất (Kết thúc)</p>
                                <select class="select2-single form-control" name="endYear">
                                    <option value="">Năm kết thúc</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year['id'] }}">{{ $year['zsym'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Loại báo cáo</p>
                                <select class="select2-single form-control" name="reportType">
                                    <option value="ti-le-hang-tron">Biểu đồ phần trăm các hãng</option>
                                    <option value="ti-le-hang-cot">Biểu đồ số lượng thiết bị của hãng theo Đơn vị quản lý</option>
                                    <option value="ti-le-kieu-tron">Biểu đồ phần trăm kiểu thiết bị</option>
                                    <option value="ti-le-kieu-cot">Biểu đồ số lượng của kiểu thiết bị theo hãng</option>
                                </select>
                            </div>
                            <div class="form-group form_submit" style="display: flex;align-items: center;margin-bottom: 15px;">
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
                url: '{{ route('admin.statisticalListAndNumberDeviceExport') }}',
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

        $('#devices').change(function(){
            $.ajax({
                url: '{{ route('admin.objectByDeviceType') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    type: $(this).val(),
                    obj: 'zManufacturer'
                }
            }).done(function(result) {
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }

                if (typeof response['success'] !== 'undefined') {
                    let html = '';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('select[name="manufacture_ids[]"]').html(html);
                }
            });

            $.ajax({
                url: '{{ route('admin.objectByDeviceType') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    type: $(this).val(),
                    obj: 'zCI_Device_Type'
                }
            }).done(function(result) {
                let response = JSON.parse(result);

                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }
                if (typeof response['success'] !== 'undefined') {
                    let html = '';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('#deviceType').html(html);
                }
            });
        });
    </script>
@endpush
