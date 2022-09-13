@extends('layouts.master')

@section('pageTitle', 'BÁO CÁO PHÂN TÍCH PHÒNG HÓA DẦU')
@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo phân tích</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Phòng hóa dầu</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px" >
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Quản lý báo cáo phân tích OLTC</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <div class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;">
                    <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="">
                        <div class="form-group form_input form_date">
                            <p>Ngày bắt đầu</p>
                            <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}" />
                        </div>
                        <div class="form-group form_input form_date">
                            <p>Ngày kết thúc</p>
                            <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}" />
                        </div>

                        <div class="form-group form_input form_date">
                            <p>Ngày bắt đầu lấy mẫu</p>
                            <input type="date" class="form-control" name="from_create" value="{{ request()->get('from_create') }}" />
                        </div>
                        <div class="form-group form_input form_date">
                            <p>Ngày kết thúc lấy mẫu</p>
                            <input type="date" class="form-control" name="to_create" value="{{ request()->get('to_create') }}" />
                        </div>

                        <div class="form-group form_input">
                            <p>Thiết bị</p>
                            <input type="text"  class="form-control" name="equipment" value="{{ request()->get('equipment') }}"/>
                        </div>
                        <div class="form-group form_input">
                            <p>Số chế tạo</p>
                            <input type="text"  class="form-control" name="manufacturing_number" value="{{ request()->get('manufacturing_number') }}" />
                        </div>
                        <div class="form-group form_input">
                            <p>Ngăn lộ</p>
                            <input type="text"  class="form-control" name="nl" value="{{ request()->get('nl') }}" />
                        </div>
                        <div class="form-group form_input">
                            <p>Trạm nhà máy</p>
                            <input type="text"  class="form-control" name="td" value="{{ request()->get('td') }}" />
                        </div>
                        <div class="form-group form_input">
                            <p>Đơn vị quản lý</p>
                            <input type="text"  class="form-control" name="dvql" value="{{ request()->get('dvql') }}" />
                        </div>
                        <div class="form-group form_input">
                            <p>Người làm biên bản</p>
                            <input type="text"  class="form-control" name="experimenter" value="{{ request()->get('experimenter') }}" />
                        </div>
                        <div class="form-group form_submit mb-0">
                            <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body min_table list_list">
            <div class="">
                <table class="table table-striped">
                    <thead class="thead-dark">
                    <tr>
                        <th class="text-center">Tên biên bản</th>
                        <th class="text-center">Ngày làm thí nghiệm</th>
                        <th class="text-center">Người làm thí nghiệm</th>
                        <th class="text-center">Xem trước</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ @$item['name'] }}</td>
                            <td class="text-center">{{ @$item['zlaboratoryDate'] ? date('d-m-Y', @$item['zlaboratoryDate']) : '' }}</td>
                            <td class="text-center">{{ trim(implode(' ', [@$item['zExperimenter.first_name'], @$item['zExperimenter.middle_name'], @$item['zExperimenter.last_name']]))  }}</td>
                            <td class="text-center">
                                <a id="open{{  @$item['id'] }}" data-toggle="modal" data-target="#modal_{{ @$item['id'] }}" style="border: none; background: none; color: #506fe4; ">
                                    <i class="dripicons-preview"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div>
        @foreach($items as $item)
            <div id="modal_{{ @$item['id'] }}" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleLargeModalLabel">Dữ liệu xem trước</h5>
                            <button id="#close{{ @$item['id'] }}" type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Tên vật chất</th>
                                    <th scope="col">Kết quả thí nghiệm</th>
                                </tr>
                                </thead>
                                <tbody id="tbody">

                                @foreach($item['zhd'] as $k => $v)
                                    <tr>
                                        <td>{{ $k }}</td>
                                        <td>{{ $v }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">Đóng</button>
                            <a href="{{ route('adminoltcAnalytic_export', ['id' => $item['id']]) }}" class="btn btn-primary">Xuất dữ liệu</a>
                            <a href="{{ route('admin.oltcAnalytic_preview', ['id' => $item['id']]) }}" class="btn btn-primary">Preview</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection
