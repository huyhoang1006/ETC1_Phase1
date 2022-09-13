@extends('layouts.master')

@section('pageTitle', 'Hóa dầu - Báo cáo tỉ lệ theo số lượng thiết bị của từng hãng sản xuất ứng với năm hoặc khoảng thời gian sản xuất')

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
                        <li class="breadcrumb-item"><a href="javaScript:void();">Phòng hóa dầu</a></li>
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
                <h4 class="m-b-30"> Báo cáo thống kê thí nghiệm theo khu vực </h4>
            </div>
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="form card-body module_search" style="align-items: flex-end">
                            <div class="form-group form_input form_date">
                                <p>Ngày bắt đầu</p>
                                <input type="date" class="form-control" id="start_date">
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Ngày kết thúc</p>
                                <input type="date" class="form-control" id="end_date">
                            </div>
                            <div class="form-group form_input">
                                <p>Thiết bị</p>
                                <select class="select2-single form-control" name="device[]" id="device" multiple>
                                    @foreach($deviceTypes as $device)
                                        <option value="{{ $device }}">{{ $device }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Đơn vị quản lý</p>
                                <select class="select2-single form-control" name="dvqls[]" id="dvqls" multiple>
                                    @foreach($dvqls as $dvql)
                                        <option value="{{ $dvql['id'] }}">{!! $dvql['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Khu vực</p>
                                <select class="select2-single form-control" name="areas_name[]" id="areas_name" multiple>
                                    @foreach($areas as $area)
                                        <option value="{!! $area['zsym'] !!}">{!! $area['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_submit" style="display: flex;align-items: center;margin-bottom: 15px;">
                                <button type="button" class="btn btn-dark btn-eport">Tìm kiếm</button>
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
        $('.btn-eport').on('click', function(){
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const device = $('#device').val();
            const dvqls = $('#dvqls').val();
            const areas_name = $('#areas_name').val()
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1'
            });
            $.ajax({
                url: "{{ route('admin.reportExperimentalByRegionExport') }}",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    _token: "{{ csrf_token() }}",
                    startDate,
                    endDate,
                    device,
                    dvqls,
                    areas_name
                },
                success: function(result){
                    if (typeof result['error'] !== 'undefined') {
                        for (let i = 0; i < result['error'].length; i++) {
                            toastr["error"](result['error'][i]);
                        }
                        $('.downloadBtn').css({
                            'display': 'none'
                        });
                        $('.loadingBox').css({
                            'opacity': '0',
                            'z-index': '-1'
                        });
                    }

                    if (typeof result['success'] !== 'undefined') {
                        $('.loadingBox').css({
                            'opacity': '0',
                            'z-index': '-1'
                        });
                        $('#previewExcel').attr('src', 'https://view.officeapps.live.com/op/embed.aspx?src=' + result['url'] + '?t={{ time() }}');
                        $('.downloadBtn').attr('href', result['url']);
                        $('.downloadBtn').css({
                            'display': 'flex'
                        });
                    }

                }
            });
        });
    </script>
@endpush
