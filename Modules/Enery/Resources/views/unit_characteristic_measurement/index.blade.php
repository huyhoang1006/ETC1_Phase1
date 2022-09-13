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
            <h4 class="m-b-30">Quản lý báo cáo thống kê phòng công nghệ năng lượng</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.boilersByManufacture') }}">
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
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Kiểu thiết bị</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Số chế tạo</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Vị trí lắp đặt</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Hãng sản xuất</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Người thí nghiệm</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Chủng loại</p>
                        <input type="text"  class="form-control" name="" value="" />
                    </div>
                    <div class="form-group form_input">
                        <p>Loại hình thí nghiệm</p>
                        <input type="text"  class="form-control" name="" value="" />
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
                                <th>Tên biên bản</th>
                                <th>Người làm thí nghiệm</th>
                                <th>Ngày làm thí nghiệm</th>
                                {{-- <th>Khu vực</th> --}}
                                <th>Ngăn lộ</th>
                                <th>Nhà máy</th>
                                <th>Trạm</th>
                                <th class="text-center">Thiết bị</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($items as $item )
                                <tr>
                                    <td>
                                        <input  id="viewCheck" value="{{ @$item['id'] }}" type="checkbox" class="mr-1">
                                    </td>
                                    <td>{{ @$item['name'] }}</td>
                                    <td>
                                        {{ trim(implode(' ', [@$item['zExperimenter1.first_name'], @$item['zExperimenter1.middle_name'], @$item['zExperimenter1.last_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter2.first_name'], @$item['zExperimenter2.middle_name'], @$item['zExperimenter2.last_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter3.first_name'], @$item['zExperimenter3.middle_name'], @$item['zExperimenter3.last_name']])) }} <br>
                                        {{ trim(implode(' ', [@$item['zExperimenter4.first_name'], @$item['zExperimenter4.middle_name'], @$item['zExperimenter4.last_name']])) }} <br>
                                    </td>
                                    <td>{{ @$item['zlaboratoryDate'] ?  date('d-m-Y', $item['zlaboratoryDate']) : '' }}</td>
                                    {{-- <td>{{ @$item['zArea.zsym'] }}</td> --}}
                                    <td>{{ @$item['zCI_Device.zrefnr_nl.zsym'] }}</td>
                                    <td>{{ @$item['zCI_Device.zrefnr_dvql.zsym'] }}</td>
                                    <td>{{ @$item['zCI_Device.zrefnr_td.zsym'] }}</td>
                                    <td>{{ @$item['zCI_Device.name'] }}</td>
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
