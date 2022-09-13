@extends('layouts.master')

@section('pageTitle', 'Rơ le - Báo cáo thống kê công tác thí nghiệm Rơ le')

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
                        <li class="breadcrumb-item"><a href="javaScript:void();">Rơ le</a></li>
                        <li class="breadcrumb-item active"><a href="">Báo cáo thống kê công tác thí nghiệm Rơ le</a></li>
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
                <h4 class="m-b-30">
                    Rơ le - Báo cáo thống kê công tác thí nghiệm Rơ le
                </h4>
            </div>
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="form card-body module_search" style="align-items: flex-end">
                            <div class="form-group form_input">
                                <p>Loại thiết bị</p>
                                <select class="select2-single form-control" name="device_type_id[]" multiple>
                                    @foreach($types as $type)
                                        <option value="{{ $type['id'] }}">{!! $type['type'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Chủng loại</p>
                                <select class="select2-single form-control" name="zCIDeviceType[]" id="deviceTypes" multiple>
                                    @foreach($allDeviceTypes as $val)
                                        <option value="{{ $val }}">{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Đơn vị quản lý</p>
                                <select class="select2-single form-control" name="dvql[]" multiple>
                                    @foreach($allManagementUnits as $val)
                                        <option value="{!! $val !!}">{!! $val !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Thời gian bắt đầu</p>
                                <input type="date" class="form-control" name="start_date">
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Thời gian kết thúc</p>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                            <div class="form-group form_input">
                                <p>Loại hình thí nghiệm</p>
                                <select class="select2-single form-control" name="ztestType[]" multiple>
                                    @foreach($typeOfExperiments as $val)
                                        <option value="{{ $val }}">{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_submit" style="display: flex;align-items: center;margin-bottom: 15px;">
                                <button type="submit" class="btn btn-dark btn-eport-cd7">Tìm kiếm</button>
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
                url: "{{ route('admin.reportRelayTestManifestSystemExport') }}",
                type: 'POST',
                dataType: 'JSON',
                data: $(this).serialize(),
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