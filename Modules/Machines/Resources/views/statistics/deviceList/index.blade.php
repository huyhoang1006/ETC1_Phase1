@extends('layouts.master')
@section('pageTitle', 'Báo cáo thống kê số lượng và danh sách thiết bị')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Phòng cao áp</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Báo cáo thống kê số lượng và danh sách thiết bị</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Báo cáo thống kê số lượng và danh sách thiết bị</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;">
                    <div class="form-group form_input form_date">
                        <p>Thiết bị</p>
                        <select class="select2-single form-control">
                            <option value="">Máy biến áp</option>
                            <option value="">Máy biến áp phân phối</option>
                            <option value="">Máy biến dòng</option>
                            <option value="">Máy biến điện áp</option>
                            <option value="">Máy cắt</option>
                            <option value="">OLTC</option>
                            <option value="">Chống sét van</option>
                            <option value="">Dao cách ly</option>
                            <option value="">Cáp lực</option>
                            <option value="">Kháng điện</option>
                            <option value="">Tụ điện</option>
                            <option value="">Mẫu cáp (xung sét cáp)</option>
                            <option value="">Mẫu cách điện</option>
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Chủng loại</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Khu vực</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zCustomer">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Loại hình thí nghiệm</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Đơn vị quản lý</p>
                        <select class="select2-single form-control">
                        </select>
                    </div>
                    <div class="form-group form_input">
                        <p>Hãng sản xuất</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zManufacturer">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Kiểu thiết bị</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zCI_Device_Kind">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Tình trạng</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Công suất</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Tổ đấu dây</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Điện áp định mức</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Dòng định mức</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Số chế tạo</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="serial_number">
                        </div>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Năm bắt đầu</p>
                        <select class="select2-single form-control" name="startYear">
                            @for ($i = date('Y'); $i >= 2015; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group form_input form_date">
                        <p>Năm kết thúc</p>
                        <select class="select2-single form-control" name="endYear">
                            @for ($i = date('Y'); $i >= 2015; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>


                    <div class="form-group form_input">
                        <p>Trạm/Nhà máy</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zrefnr_td">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Ngăn lộ/Hệ thống</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zrefnr_nl">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Thiết bị</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="name">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Năm sản xuất</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zYear_of_Manafacture">
                        </div>
                    </div>
                    <div class="form-group form_input">
                        <p>Nước sản xuất</p>
                        <div class="input-group">
                            <input type="text" class="form-control" name="zCountry">
                        </div>
                    </div>
                    <div class="form-group form_submit mb-0">
                        <button type="button" id="submitBtn" class="btn btn-dark">Tìm kiếm</button>
                    </div>
                </form>
            </div>
            <div class="card m-b-30">
                <div class="card-body module_search v-right" style=" padding-top: 20px;padding-bottom: 20px;">
                    <div class="form-group form_input mb-0">
                        <select class="form-control" name="report_type" id="ViewReport">
                            <option value="Báo cáo kiểm tra bên ngoài">Báo cáo kiểm tra bên ngoài</option>
                            <option value="Báo cáo điện trở cách điện">Báo cáo điện trở cách điện</option>
                            <option value="Báo cáo điện trở tiếp xúc">Báo cáo điện trở tiếp xúc</option>
                            <option value="Báo cáo thời gian cắt">Báo cáo thời gian cắt</option>
                            <option value="Báo cáo thời gian ngừng tiếp xúc ở chế độ O-CO">Báo cáo thời gian ngừng tiếp xúc ở chế độ O-CO</option>
                            <option value="Báo cáo thời gian đóng">Báo cáo thời gian đóng</option>
                            <option value="Báo cáo thời gian tiếp xúc ở chế độ CO">Báo cáo thời gian tiếp xúc ở chế độ CO</option>
                            <option value="Báo cáo áp lực khí nạp ở t=20 độ C">Báo cáo áp lực khí nạp ở t=20 độ C</option>
                            <option value="Báo cáo điện trở cách điện cuộn đóng/ cuộn cắt (MW)">Báo cáo điện trở cách điện cuộn đóng/ cuộn cắt (MW)</option>
                            <option value="Báo cáo điện trở cách điện động cơ tính năng">Báo cáo điện trở cách điện động cơ tính năng</option>
                            <option value="Báo cáo kiểm tra cơ cấu truyền động">Báo cáo kiểm tra cơ cấu truyền động</option>
                            <option value="Báo cáo thử điện áp AC f=50Hz trong 1 phút">Báo cáo thử điện áp AC f=50Hz trong 1 phút</option>
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0">
                        <button id="btnViewReport" type="button" class="btn btn-dark">Xem báo cáo</button>
                    </div>
                </div>
            </div>

            <div class="card dataBox" style="background: none;box-shadow: none;">
                <div class="card-body min_table list_list" style="display: none;">
                    <div id="dataTable">
                    </div>
                </div>
                <div class="loadingBox" style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0;">
                    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px;">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
    </script>
@endpush
