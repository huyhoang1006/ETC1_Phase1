@extends('layouts.master')

@section('pageTitle', 'Báo cáo CNNL')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="#">Phòng công nghệ năng lượng</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('content')
<div class="row dashboard-index detail-report" style="padding-top: 0px">
    <div class="col-lg-12 col-xl-12">
        <h4 class="m-b-30">Quản lý báo cáo so sánh kết quả thí nghiệm thông số tua bin hơi</h4>
    </div>
    <div class="col-sm-12 col-xl-12">
        <div class="card m-b-30">
            <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
            </div>
            <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.thi_nghiem_tua_bin_hoi') }}">
                <div class="form-group form_input form_date">
                    <p>Ngày bắt đầu</p>
                    <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                </div>
                <div class="form-group form_input form_date">
                    <p>Ngày kết thúc</p>
                    <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
                </div>

                <div class="form-group form_input">
                    <p>Thiết bị</p>
                    <input type="text"  class="form-control" name="equipment" value="{{ request()->get('equipment') }}"/>
                </div>
                <div class="form-group form_input">
                    <p>Kiểu thiết bị</p>
                    <input type="text"  class="form-control" name="device_type" value="{{ request()->get('device_type') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Số chế tạo</p>
                    <input type="text"  class="form-control" name="manufacturing_number" value="{{ request()->get('manufacturing_number') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Vị trí lắp đặt</p>
                    <input type="text"  class="form-control" name="installation_location" value="{{ request()->get('installation_location') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Hãng sản xuất</p>
                    <input type="text"  class="form-control" name="manufacturer" value="{{ request()->get('manufacturer') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Người thí nghiệm</p>
                    <input type="text"  class="form-control" name="experimenter" value="{{ request()->get('experimenter') }}" />
                </div>
                <div class="form-group form_input">
                    <p>Chủng loại</p>
                    <input type="text"  class="form-control" name="species" value="{{ request()->get('species') }}" />
                </div>

                <div class="form-group form_input">
                    <p>Loại hình thí nghiệm</p>
                    <input type="text"  class="form-control" name="type_experiment" value="{{ request()->get('type_experiment') }}" />
                </div>

                <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 300px;">
                    <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                </div>
                <button id="btnViewReport" type="button" class="btn btn-primary" disabled="" onclick="gotoReport()" >Xem báo cáo</button>
            </form>
        </div>
        <div class="card m-b-30">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="thead-blue">
                            <tr>
                                <th></th>
                                <th>STT</th>
                                <th>Tên thiết bị</th>
                                <th>Ngày làm thí nghiệm</th>
                                <th>Kiểu thiết bị</th>
                                <th>Chủng loại</th>
                                <th>Số chế tạo</th>
                                <th>Vị trí lắp đặt</th>
                                <th>Hãng sản xuất</th>
                                <th>Người thí nghiệm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $key => $item )
                                <tr>
                                    <td>
                                        <input  id="viewCheck" value="{{ @$item['id'] }}" type="checkbox" class="mr-1">
                                    </td>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item['zCI_Device.name'] ??'' }}</td>
                                    <td>{{ @$item['zlaboratoryDate'] ? date('d-m-Y', @$item['zlaboratoryDate']) : '' }}</td>
                                    <td>{!! @$item['zCI_Device.zCI_Device_Kind.zsym'] ??'' !!}</td>
                                    <td>{!! @$item['zCI_Device.zCI_Device_Type.zsym'] ??''!!}</td>
                                    <td>{!! @$item['zCI_Device.serial_number'] ??'' !!}</td>
                                    <td>{!! @$item['zvitrilapdat'] ??''!!}</td>
                                    <td>
                                        {!! @$item['zCI_Device.zManufacturer.zsym'] ??'' !!}
                                    </td>
                                    <td>
                                        {{ trim(implode(' ', [@$item['zExperimenter1.last_name'], @$item['zExperimenter1.first_name'], @$item['zExperimenter1.middle_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter2.last_name'], @$item['zExperimenter2.first_name'], @$item['zExperimenter2.middle_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter3.last_name'], @$item['zExperimenter3.first_name'], @$item['zExperimenter3.middle_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter4.last_name'], @$item['zExperimenter4.first_name'], @$item['zExperimenter4.middle_name']])) }} <br>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('input[type=checkbox]').on('change', function (e) {
                let totalChecked = $('input[type=checkbox]:checked').length;
                if (totalChecked === 0) {
                    $(this).prop('checked', false);
                    $('#btnViewReport').attr('disabled', 'disabled')
                }
                else {
                    $('#btnViewReport').removeAttr('disabled')
                }
            });
        });

        function gotoReport() {
            let ids = [];
            let viewchecks = $('#viewCheck[type=checkbox]:checked').each(function(e){ids.push($(this).val())});
            console.log(viewchecks.length);
            // if(viewCheck.length > 1) {
                window.location.href = '{{ route('admin.thi_nghiem_tua_bin_hoi_preview') }}?ids=' +  ids.join(',');
            // }
        }
    </script>
@endpush
