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
            <h4 class="m-b-30">Báo cáo thống kê công tác thí nghiệm - Máy biến áp</h4>
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
                            <p>Đơn vị quản lý</p>
                            <select  class="select2-single form-control" id="dvql" name="dvql">
                                <option value="">-- Khu vực --</option>
                                @foreach ($dvqls as $dvql)
                                    <option value="{{ $dvql['id'] }}">{{ @$dvql['zsym'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_input">
                            <p>Trạm</p>
                            <select  class="select2-single form-control" id="td" name="td">
                                <option value="">-- Trạm --</option>
                                @foreach ($tds as $td)
                                    <option value="{{ $td['zsym'] }}">{{ @$td['zsym'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_input">
                            <p>Thiết bị</p>
                            <select  class="select2-single form-control" id="deviceNames" name="deviceNames[]" multiple="multiple">
                                @foreach ($items as $item)
                                    <option value="{{ $item['name'] }}">{{ @$item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_submit mb-0">
                            <button type="submit" class="btn btn-dark" id="btn-submit">Tìm kiếm</button>
                        </div>
                        <input type="hidden" id="list-device">
                    </div>
                    
                </form>
            </div>
            <div class="col-lg-12 col-xl-12 mb-5 button-preview">
                <button type="button" class="btn btn-primary" id="btn-preview-report" disabled>Xem báo cáo</button>
            </div>
            <div class="card m-b-30">
                <div class="card-body" id="card-body">
                    <div class="table-responsive" id="table-devices">
                        @include('machines::statisticalExperimental.device')
                    </div>
                    <div class="table-responsive" id="table-reports">
                        <div class="col-12" style="padding: 0; margin-bottom: 20px;">
                            <button class="btn btn-default back-to-device">Quay lại bảng thiết bị</button>
                            <button class="btn btn-default export-file">Xuất file</button>
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
@endsection
@push('scripts')
    <script>
        $(document).on('change', 'input:checkbox', function(e) {
            const lengthCheck = $('#viewCheck[type=checkbox]:checked').length;
            console.log(lengthCheck);
            if(lengthCheck > 0){
                $('#btn-preview-report').prop('disabled', false);
            }else{
                $('#btn-preview-report').prop('disabled', true);
            }
        });

        $('#dvql').on('change', function(){
            const id = $(this).val();
            const url = '{{ route("admin.getTD") }}';
            const optionBlack = 'Trạm/Nhà máy';
            const elementAppend = '#td';
            $(elementAppend).attr('disabled', 'disabled');
            $.ajax({
                url: url,
                method: "GET",
                data: { id },
                dataType: 'html',
            }).done(function(result) {
                $(elementAppend).removeAttr('disabled');
                let response = JSON.parse(result);
                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }
                if (typeof response['success'] !== 'undefined') {
                    let html = `<option value="">${optionBlack}</option>`;
                    $.each(response['data'], function(index, value){
                        html += `<option value="${value['zsym']}">${value['zsym']}</option>`;
                    });
                    $(elementAppend).html(html);
                }
            });
        }); 
        
        // ajax get device form request
        function ajaxFilterDevice(request){
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1',
                'position': 'absolute',
            });
            $('.loadingBox img').css({
                'top' : window.pageYOffset - 450,
            });

            const url = '{{ route('admin.ajaxFilterDevice') }}';
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
                    $('#table-reports').hide();
                    $('.button-preview').show();
                }
            });
        }
        // btn show/hide table device/report
        $('.back-to-device').on('click', function(){
            $('#table-reports').hide();
            $('#table-devices').show();
            $('#block-report').hide();
            $('.button-preview').show();
        });
        // hanlde submit form
        $('#btn-submit').click(function(e){
            e.preventDefault();
            ajaxFilterDevice($( '#form' ).serialize());
            $('#btn-preview-report').prop('disabled', true);
        });
        $('#btn-preview-report').on('click', function(){
            let ids = [];
            let viewchecks = $('#viewCheck[type=checkbox]:checked').each(function(e){
                ids.push($(this).val());
            });
            $('#list-device').val(ids.join(','));
            $('.loadingBox').css({
                'opacity': '.5',
                'z-index': '1',
                'position': 'absolute',
            });
            $('.loadingBox img').css({
                'top' : window.pageYOffset - 450,
            });
            const url = '{{ route('admin.getNumberOfExperiments') }}';
            let data = $('#form').serialize()+ '&ids=' + ids.join(',');
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'html',
                data: data
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
                    $('.wrapper-reports').html(response['html']);
                    $('#table-devices').hide();
                    $('.button-preview').hide();
                    $('#table-reports').show();
                }
            });
        });
        $('.export-file').on('click', function(){
            let data = $('#form').serialize();
            const ids = $('#list-device').val();
            data += '&ids='+ids;
            const url = '{{ route('admin.getNumberOfExperimentsExport') }}';
            window.location.href = `${url}?${data}`;
        });
    </script>
@endpush
