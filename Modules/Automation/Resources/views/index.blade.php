@extends('layouts.master')

@section('pageTitle', 'QUẢN LÝ THIẾT BỊ')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thống kê tự động hóa</li>
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
                        <form class="form card-body module_search" method="get" action="{{ route('admin.automation') }}">
                            <div class="form-group form_input form_date">
                                <p>Ngày bắt đầu</p>
                                <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                            </div>
                            <div class="form-group form_input form_date">
                                <p>Ngày kết thúc</p>
                                <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
                            </div>
                            <div class="form-group form_input">
                                <p>Khu vực</p>
                                <select class="select2-single form-control" name="area">
                                    <option value="">Khu vực</option>
                                    @foreach($zArea as $key => $val)
                                        <option {{ request()->get('area') == $key ? 'selected' : '' }} value="{{ $key }}">{!! $val !!}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="area_name" value="{{ request()->get('area_name') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Trạm / Nhà máy</p>
                                <select class="select2-single form-control" name="td_names[]" id="td_names" multiple="multiple">
                                    @foreach($tds as $val)
                                        <option {{ in_array($val['zsym'], request()->get('td_names') ?? []) ? 'selected' : '' }} value="{{ $val['zsym'] }}">{!! $val['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Ngăn lộ / Hệ thống</p>
                                <select class="select2-single form-control" name="nl_names[]" id="nl_names" multiple="multiple">
                                    @foreach($nls as $val)
                                        <option {{ in_array($val['zsym'], request()->get('nl_names') ?? []) ? 'selected' : '' }} value="{{ $val['zsym'] }}">{!! $val['zsym'] !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form_input">
                                <p>Thiết bị</p>
                                <input type="text" class="form-control" name="name" value="{{ request()->get('name') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Loại thiết bị</p>
                                <select class="select2-single form-control" name="devices">
                                    <option value="">Loại thiết bị</option>
                                    @foreach($types as $key => $type)
                                        <option {{ request()->get('devices') == $key ? 'selected' : '' }} value="{{ $key }}">{!! $type !!}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="devices_name" value="{{ request()->get('devices_name') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Hãng sản xuất</p>
                                <select class="select2-single form-control" name="manufacture">
                                    <option value="">Hãng sản xuất</option>
                                    @foreach($manufactures as $manufacture)
                                        <option {{ request()->get('manufacture') == $manufacture ? 'selected' : '' }} value="{{ $manufacture }}">{!! $manufacture !!}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="manufacture_name" value="{{ request()->get('manufacture_name') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Kiểu</p>
                                <input type="text" class="form-control" name="type" value="{{ request()->get('type') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Số chế tạo</p>
                                <input type="text" class="form-control" name="series" value="{{ request()->get('series') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Năm sản xuất</p>
                                <input type="number" class="form-control" name="year" value="{{ request()->get('year') }}" min="1970">
                            </div>
                            <div class="form-group form_input">
                                <p>Nước sản xuất</p>
                                <select class="select2-single form-control" name="country">
                                    <option value="">Nước sản xuất</option>
                                    @foreach($zCountry as $key => $val)
                                        <option {{ request()->get('country') == $key ? 'selected' : '' }} value="{{ $key }}">{!! $val !!}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="country_name" value="{{ request()->get('country_name') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Firmware/ OS</p>
                                <input type="text" class="form-control" name="firmware" value="{{ request()->get('firmware') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Phần mềm</p>
                                <input type="text" class="form-control" name="software" value="{{ request()->get('software') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Phiên bản phần mềm</p>
                                <input type="text" class="form-control" name="version" value="{{ request()->get('version') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Địa chỉ mạng</p>
                                <input type="text" class="form-control" name="ip_address" value="{{ request()->get('ip_address') }}">
                            </div>
                            <div class="form-group form_input">
                                <p>Thời gian lắp đặt</p>
                                <input type="date" class="form-control" name="installation_time" value="{{ request()->get('installation_time') }}"/>
                            </div>
                            <div class="form-group form_input">
                                <p>Chủng loại</p>
                                <select class="select2-single form-control" name="zCI_Device_Type">
                                    <option value="">Chủng loại</option>
                                    @foreach($deviceTypes as $key => $deviceType)
                                        <option {{ request()->get('zCI_Device_Type') == $key ? 'selected' : '' }} value="{{ $key }}">{!! $deviceType !!}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="zCI_Device_Type_name" value="{{ request()->get('zCI_Device_Type_name') }}">
                            </div>
                            <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 300px;">
                                <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                                <a href="{{ route('admin.automation.export', ['data' => $request]) }}" style="width: 100%;height: 38px;box-shadow: none;padding-left: 0;padding-right: 0;background: #1a4796;color: #fff;display: flex;justify-content: center;align-items: center;border-radius: 3px;font-size: 15px;margin-left: 10px;border: 1px solid #141d46;">Xuất file</a>
                            </div>
                        </form>
                    </div>
                    <?php
                        $maxReport = 0;
                        foreach ($devices as $item) {
                            if (!empty($item['report']) && count($item['report']) > $maxReport) {
                                $maxReport = count($item['report']);
                            }
                        }
                    ?>
                    <div class="card">
                        <div class="card-body min_table list_list">
                            <div class="table_minwidth">
                                <table class="table table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Khu vực</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Trạm/Nhà máy</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Ngăn lộ/Hệ thống</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Thiết bị</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Hãng sản xuất</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Chủng loại</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Kiểu</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Số chế tạo</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Năm sản xuất</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Nước sản xuất</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Firmware/OS</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Phần mềm</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Phiên bản phần mềm</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Địa chỉ mạng</th>
                                            <th @if (!empty($maxReport)) rowspan="2" @endif>Thời gian lắp đặt</th>
                                            @if (!empty($maxReport))
                                                @for ($i = 1; $i <= $maxReport; $i++)
                                                    <th colspan="4">Thông tin thí nghiệm lần {{ $i }}</th>
                                                @endfor
                                            @endif
                                        </tr>
                                        @if (!empty($maxReport))
                                            <tr>
                                                @for ($i = 1; $i <= $maxReport; $i++)
                                                    <th>Thời gian</th>
                                                    <th>Người thí nghiệm {{ $i }}</th>
                                                    <th>Nội dung thí nghiệm {{ $i }}</th>
                                                    <th>Note {{ $i }}</th>
                                                @endfor
                                            </tr>
                                        @endif
                                    </thead>
                                    <tbody>
                                        @foreach($devices as $item)
                                            <tr>
                                                <td>{{ @$item['zArea.zsym'] }}</td>
                                                <td>{{ @$item['zrefnr_td.zsym'] }}</td>
                                                <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
                                                <td>
                                                    @if (!empty($item['handle_id']))
                                                        <a href="{{ route('admin.device.detail', [@$item['handle_id']]) }}">{{ @$item['name'] }}</a>
                                                    @else
                                                        <a>{{ @$item['name'] }}</a>
                                                    @endif
                                                </td>
                                                <td>{{ @$item['zManufacturer.zsym'] }}</td>
                                                <td>{{ @$item['zCI_Device_Type.zsym'] }}</td>
                                                <td>{{ @$item['zCI_Device_Kind.zsym'] }}</td>
                                                <td>{{ @$item['serial_number'] }}</td>
                                                <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                                                <td>{{ @$item['zCountry.name'] }}</td>
                                                <td>{{ !empty($item['info']['zhedieuhanh.zsym']) ? $item['info']['zhedieuhanh.zsym'] : '' }}</td>
                                                <td>{{ !empty($item['info']['zSoftware.zsym']) ? $item['info']['zSoftware.zsym'] : '' }}</td>
                                                <td>{{ !empty($item['info']['zversion']) ? $item['info']['zversion'] : '' }}</td>
                                                <td>{{ !empty($item['info']['zIP']) ? $item['info']['zIP'] : '' }}</td>
                                                <td>{{ !empty($item['info']['zTGLD']) ? date('d/m/Y', $item['info']['zTGLD']) : '' }}</td>
                                                @if (!empty($item['report']))
                                                    @foreach ($item['report'] as $key => $report)
                                                        <td>{{ date('d/m/Y', @$report['creation_date']) }}</td>
                                                        <td>{{ implode(' ', [@$report['zExperimenter.last_name'], @$report['zExperimenter.middle_name'], @$report['zExperimenter.first_name'], ]) }}</td>
                                                        <td>{{ @$report['experiment_type'] }}</td>
                                                        <td>{{ @$report['zNotes'] }}</td>
                                                    @endforeach
                                                    @if (count($item['report']) < $maxReport)
                                                        @for ($i = 1; $i <= $maxReport - count($item['report']); $i++)
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                        @endfor
                                                    @endif
                                                @elseif (!empty($maxReport))
                                                    @for ($i = 1; $i <= $maxReport; $i++)
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                    @endfor
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if(!count($devices))
                                            <tr>
                                                <td colspan="14" class="text-center">Không tìm thấy bản ghi nào</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('select[name="area"]').change(function(){
            if ($(this).val() !== '') {
                $('input[name="area_name"]').val($(this).find(':selected').text());
            }
        });

        $('select[name="manufacture"]').change(function(){
            if ($(this).val() !== '') {
                $('input[name="manufacture_name"]').val($(this).find(':selected').text());
            }
        });

        $('select[name="country"]').change(function(){
            if ($(this).val() !== '') {
                $('input[name="country_name"]').val($(this).find(':selected').text());
            }
        });

        $('select[name="zCI_Device_Type"]').change(function(){
            if ($(this).val() !== '') {
                $('input[name="zCI_Device_Type_name"]').val($(this).find(':selected').text());
            }
        });

        $('select[name="devices"]').change(function(){
            if ($(this).val() !== '') {
                $('input[name="devices_name"]').val($(this).find(':selected').text());
            }

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
                    let html = '<option value="">Hãng sản xuất</option>';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('select[name="manufacture"]').html(html);
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
                    let html = '<option value="">Chủng loại</option>';
                    for (let i = 0; i < Object.keys(response['data']).length; i++) {
                        html += '<option value="' + Object.keys(response['data'])[i] + '">' + response['data'][Object.keys(response['data'])[i]] + '</option>';
                    }
                    $('select[name="zCI_Device_Type"]').html(html);
                }
            });
        });
        $('#td_names').on('change', function(){
            $('#nl_names').prop('disabled', true);
            $.ajax({
                url: '{{ route('admin.automation.ajaxGetNL') }}',
                type: 'GET',
                dataType: 'html',
                data: {
                    td_names: $(this).val(),
                },
                success: function(result){
                    let response = JSON.parse(result);
                    $('#nl_names').prop('disabled', false);
                    if (typeof response['error'] !== 'undefined') {
                        for (let i = 0; i < response['error'].length; i++) {
                            toastr["error"](response['error'][i]);
                        }
                    }
                    if (typeof response['success'] !== 'undefined') {
                        let html = '';
                        $.each(response['data'], function(index, value){
                            html += `<option value="${value['zsym']}">${value['zsym']}</option>`;
                        });
                        $('#nl_names').html(html);
                    }
                }
            })
        });
    </script>
@endpush
