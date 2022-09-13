@extends('layouts.master')

@section('pageTitle', 'PXCĐ - Báo cáo thống kê cáp và dây dẫn đã thí nghiệm - Báo cáo theo đơn vị sử dụng - Bảng số liệu cho từng đơn vị')

@push('css')
    <style>
        .table .thead-dark th, .min_table .table_minwidth td {
            border: 1px solid #000
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
                        <li class="breadcrumb-item active"><a href="">Phòng phân xưởng cơ điện</a></li>
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
                <h4 class="m-b-30">Báo cáo thống kê cáp và dây dẫn đã thí nghiệm - Báo cáo theo đơn vị sử dụng - Bảng số liệu cho từng đơn vị</h4>
            </div>
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="form card-body module_search" style="align-items: flex-end" action="{{ route('admin.figuresForEachUnitExport') }}" method="get">
                            <div class="form-group form_input form_date">
                                <p>Năm bắt đầu thống kê</p>
                                <select class="select2-single form-control" name="startYear">
                                    @for ($i = date('Y'); $i >= 2015; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Năm kết thúc thống kê</p>
                                <select class="select2-single form-control" name="endYear">
                                    @for ($i = date('Y'); $i >= 2015; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Đơn vị quản lý</p>
                                <select class="select2-single form-control" name="zDVQL[]" multiple="multiple">
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit['id'] }}">{!! $unit['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_submit" style="display: flex;align-items: center;margin-bottom: 15px;">
                                <button type="submit" class="btn btn-dark" id="submitBtn">Tìm kiếm</button>
                                <button type="submit" class="btn btn-dark" id="exportBtn" style="margin-left: 10px;display: none;">Xuất file</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card dataBox" style="background: none;box-shadow: none;">
                    <div class="card-body min_table list_list" style="display: none;">
                        <div style="overflow-x: scroll">
                            <div class="table_minwidth" id="dataTable">
                            </div>
                        </div>
                    </div>
                    <div class="loadingBox" style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                        <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#submitBtn').click(function(e){
            e.preventDefault();
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1'
            });
            $('.dataBox').css({
                'min-height': '160px'
            });

            $.ajax({
                url: '{{ route('admin.figuresForEachUnitPreview') }}',
                type: 'POST',
                dataType: 'html',
                data: $('.module_search').serialize()
            }).done(function(result) {
                $('.loadingBox').css({
                    'opacity': '0',
                    'z-index': '-1'
                });
                $('.dataBox').css({
                    'height': 'auto',
                    'background': '#fff',
                    'box-shadow': '0 10px 30px 0 rgb(24 28 33 / 5%)'
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
                    $('#dataTable').html(response['html']);
                    $('#exportBtn').css({
                        'display': 'inline-block'
                    });
                    $('.dataBox .min_table').css({
                        'display': 'block'
                    });
                }
            });
        });
    </script>
@endpush
