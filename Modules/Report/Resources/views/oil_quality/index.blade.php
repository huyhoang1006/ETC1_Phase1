@extends('layouts.master')

@section('pageTitle', 'Hóa dầu - Báo cáo chất lượng dầu')

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
            <div class="col-lg-12 col-xl-12">
                <h4 class="m-b-30">{{$title??''}}</h4>
            </div>
            <div class="col-lg-12">
                <div class=" m-b-30">
                    <div class="card m-b-30">
                        <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                            <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                        </div>
                        <form class="form card-body module_search" style="align-items: flex-end" action="{{ $route_form??'' }}" method="get">
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
                                <select class="select2-single form-control" name="dvqls[]" id="dvqls" multiple>
                                    @foreach($dvqls as $dvql)
                                        <option value="{{ $dvql['id'] }}" {{ in_array($dvql['id'], (request()->get('dvqls') ?? [])) ? 'selected' : '' }}>{!! $dvql['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Loại thiết bị</p>
                                <select class="select2-single form-control" name="devices_name[]" id="devices_name" multiple>
                                    @foreach($deviceTypes as $device)
                                        <option value="{{ $device }}" {{ in_array($device, (request()->get('devices_name') ?? [])) ? 'selected' : '' }}>{{ $device }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Tên thiết bị</p>
                                <select class="select2-single form-control" name="listDeviceName[]" id="listDeviceName" multiple>
                                    @foreach($listDeviceName as $deviceName)
                                        <option value="{{ $deviceName['name'] }}" {{ in_array($deviceName['name'], (request()->get('listDeviceName') ?? [])) ? 'selected' : '' }}>{{ $deviceName['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_submit" style="display: flex;align-items: center;margin-bottom: 15px;width: 270px">
                                <button style="margin-right: 20px" type="button" class="btn btn-dark btn-search">Tìm kiếm</button>
                                <button id="btnViewReport" type="button" class="btn btn-dark" disabled="" onclick="gotoReport()" >Xem báo cáo</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card m-b-30">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-blue">
                                <tr>
                                    <th style="font-weight: 700;text-align: center;"></th>
                                    <th>STT</th>
                                    <th>Ngày lấy mẫu</th>
                                    <th>Đơn vị quản lý</th>
                                    <th>Trạm/Nhà máy</th>
                                    <th>Ngăn lộ</th>
                                    <th>Thiết bị</th>
                                    <th>Số biên bản</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                    $count = 1;
                                ?>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                             <input  id="viewCheck" value="{{ @$item['id'] }}" type="checkbox" class="mr-1">
                                        </td>
                                        <td>{{$count}}</td>
                                        <td>{{$item['zsamplingDate_sync']??''}}</td>
                                        <td>{{$item['zrefnr_dvql.zsym']??''}}</td>
                                        <td>{{$item['zrefnr_td.zsym']??''}}</td>
                                        <td>{{$item['zrefnr_nl.zsym']??''}}</td>
                                        <td>{{$item['zCI_Device.name']??''}}</td>
                                        <td>{{$item['name']??''}}</td>
                                    </tr>
                                    <?php
                                        $count++;
                                    ?>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(".btn-search").click(function(event) {
            const from = $('.date-from').val();
            const to = $('.date-to').val();
            if(from != '' && to != '' && from > to){
                toastr["error"]('Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc!');
            }else{
                $('form').submit();
            }
        });
        $(function () {
            $('input[type=checkbox]').on('change', function (e) {
                let totalChecked = $('input[type=checkbox]:checked').length;

                if (totalChecked > 100) {
                    $(this).prop('checked', false);
                    alert("Chỉ được chọn tối đa 100 báo cáo");
                }
                if (totalChecked === 0) {
                    $('#btnViewReport').attr('disabled', 'disabled')
                }
                else {
                    $('#btnViewReport').removeAttr('disabled')
                }
            });
        });
        $('#devices_name').on('change', function(){
            const devices_name = $(this).val();
            if(devices_name.length > 0){
                $('#listDeviceName').prop('disabled', true);
                $.ajax({
                    method: 'GET',
                    url: "{{route('admin.ajaxGetListDeviceName')}}",
                    data: { devices_name },
                    dataType: 'JSON',
                    success: function(result){
                        $('#listDeviceName').prop('disabled', false);
                        if (typeof result['error'] !== 'undefined') {
                            for (let i = 0; i < result['error'].length; i++) {
                                toastr["error"](result['error'][i]);
                            }
                        }
                        if (typeof result['success'] !== 'undefined') {
                            let html = '';
                            $.each(result['data'], function(index, value){
                                html += `<option value="${value['name']}">${value['name']}</option>`;
                            });
                            $('#listDeviceName').html(html);
                        }
                    }
                });
            }else{
                $('#listDeviceName').html('');
            }
        });
        function gotoReport() {
            let ids = [];
            let viewchecks = $('#viewCheck[type=checkbox]:checked').each(function(e){ids.push($(this).val())});
            let totalChecked = $('input[type=checkbox]:checked').length;
            const request = $('.module_search').serialize();
            window.location.href = '{{ $route_preview??'' }}?ids=' +  ids.join(',')+`&${request}`;
        }
    </script>
@endpush
