@extends('layouts.master')
@section('pageTitle', 'QUẢN LÝ THIẾT BỊ')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo phân tích</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Phòng cao áp</a></li>
                        <li class="breadcrumb-item active" aria-current="page">MBA (Máy biến áp)</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('content')
<div class="row dashboard-index detail-report" style="padding-top: 0px">
    <div class="col-lg-12 col-xl-12">
        <h4 class="m-b-30">Quản lý báo cáo máy biến áp phòng cao áp</h4>
    </div>
    <div class="col-sm-12 col-xl-12">
        <div class="card m-b-30">
            <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
            </div>
            <div class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;">
                <div class="form-group form_input form_date">
                    <p>Ngày bắt đầu</p>
                    <div class="input-group">
                        <input type="text" id="default-date" class="datepicker-here form-control" placeholder="dd/mm/yyyy" aria-describedby="basic-addon2"/>
                        <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon2"><i class="feather icon-calendar"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group form_input form_date">
                    <p>Ngày kết thúc</p>
                    <div class="input-group">
                        <input type="text" id="default-date2" class="datepicker-here form-control" placeholder="dd/mm/yyyy" aria-describedby="basic-addon3"/>
                        <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon3"><i class="feather icon-calendar"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group form_input">
                    <p>Khu vực</p>
                    <select class="select2-single form-control" name="state">
                        <option>Khu Vực</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Trạm / Nhà máy</p>
                    <select class="select2-single form-control" name="state">
                        <option>Khu Vực</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                        <option value="hn">Hưng Yên</option>
                        <option value="hy">Hà Nam</option>
                        <option value="hy">Nam Định</option>
                        <option value="hy">Hòa Bình</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Ngăn lộ</p>
                    <select class="select2-single form-control" name="state">
                        <option>Ngăn lộ</option>
                        <option value="hn">MBA TD32</option>
                        <option value="hy">Ngăn 373</option>
                        <option value="hy">TU C31</option>
                        <option value="hy">Ngăn MBA T1</option>
                        <option value="hy">Hệ thống SCADA</option>
                        <option value="hy">Thanh cái C31</option>
                        <option value="hy">Ngăn TU C31</option>
                        <option value="hy">Ngăn MBA T1</option>
                        <option value="hy">Tủ AC,DC</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Năm sản xuất</p>
                    <select class="select2-single form-control" name="state">
                        <option>Năm sản xuất</option>
                        <option value="hn">2021</option>
                        <option value="hy">2020</option>
                        <option value="hy">2019</option>
                        <option value="hy">2018</option>
                        <option value="hy">2017</option>
                        <option value="hy">2016</option>
                        <option value="hy">2015</option>
                        <option value="hy">2014</option>
                        <option value="hy">2013</option>
                        <option value="hy">2012</option>
                        <option value="hy">2011</option>
                        <option value="hy">2010</option>
                        <option value="hy">2009</option>
                        <option value="hy">2008</option>
                        <option value="hy">2007</option>
                        <option value="hy">2006</option>
                        <option value="hy">2005</option>
                        <option value="hy">2004</option>
                        <option value="hy">2003</option>
                        <option value="hy">2002</option>
                        <option value="hy">2001</option>
                        <option value="hy">2000</option>
                        <option value="hy">1999</option>
                        <option value="hy">1998</option>
                        <option value="hy">1997</option>
                        <option value="hy">1996</option>
                        <option value="hy">1995</option>
                        <option value="hy">....</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Hãng sản xuất</p>
                    <select class="select2-single form-control" name="country">
                        <option>Hãng sản xuất</option>
                        <option>Hãng sản xuất 1</option>
                        <option>Hãng sản xuất 2</option>
                        <option>Hãng sản xuất 3</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Nước sản xuất</p>
                    <select class="select2-single form-control" name="country">
                        <option>Nước sản xuất</option>
                        <option>Đức</option>
                        <option value="hn">Nhật Bản</option>
                        <option value="hy">Mỹ</option>
                    </select>
                </div>
                <div class="form-group form_input">
                    <p>Công suất</p>
                    <input type="text"  class="form-control" />
                </div>
                <div class="form-group form_input">
                    <p>Tổ đấu dây</p>
                    <input type="text"  class="form-control" />
                </div>
                <div class="form-group form_input">
                    <p>Điện áp định mức</p>
                    <input type="text"  class="form-control" />
                </div>
                <div class="form-group form_input">
                    <p>Kiểu</p>
                    <input type="text"  class="form-control" />
                </div>
                <div class="form-group form_input">
                    <p>Số chế tạo</p>
                    <input type="text"  class="form-control" />
                </div>
                <div class="form-group form_submit mb-0">
                    <button type="button" class="btn btn-dark">Tìm kiếm</button>
                </div>
            </div>
        </div>
        <div class="card m-b-30">
            <div class="card-body module_search v-right" style=" padding-top: 20px;padding-bottom: 20px;">
                <div class="form-group form_input mb-0">
                    <select class="select2-single form-control" name="state" id="ViewReport">
                        <option>Chọn loại báo cáo</option>
                        <option value="1">Báo cáo kiểm tra bên ngoài</option>
                        <option value="2">Báo cáo dòng điện và tổn hao không tải</option>
                        <option value="">Báo cáo tổ đấu dây</option>
                        <option value="">Báo cáo trị số tang cách sứ</option>
                        <option value="">Báo cáo điện trở một chiều các cuộn dây</option>
                        <option value="">Báo cáo tỉ số biến đổi</option>
                        <option value="">Báo cáo trị số tang và điện dung các cuộn dây</option>
                        <option value="">Báo cáo thống kê công tác thí nghiệm</option>
                    </select>
                </div>
                <div class="form-group form_submit mb-0">
                    <button id="btnViewReport" type="button" class="btn btn-dark" disabled="" onclick="gotoReport()">Xem báo cáo</button>
                </div>
            </div>
        </div>
        <div class="card m-b-30">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="thead-blue">
                            <tr>
                                <th>Ngày Làm Thí Nghiệm</th>
                                <th>Khu Vực</th>
                                <th>Trạm/ Nhà Máy</th>
                                <th>Ngăn lộ/ Hệ Thống</th>
                                <th>Thiết Bị</th>
                                <th>Hãng Sản Xuất</th>
                                <th>Kiểu</th>
                                <th>Số Chế Tạo</th>
                                <th>Năm Sản Xuất</th>
                                <th>Nước Sản Xuất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Bắc Kạn</td>
                                <td>TNHC Trạm 110kV Ngọc Linh</td>
                                <td>171</td>
                                <td>Máy biến áp - HoangBL</td>
                                <td>Siemens</td>
                                <td>RU4S-C-D220</td>
                                <td>2132gh4545</td>
                                <td>1999</td>
                                <td>Germany</td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Hà Nam</td>
                                <td>Trạm 110kV Kim Bảng</td>
                                <td>MBAT5</td>
                                <td>Máy biến áp Test 1</td>
                                <td>UNINDO</td>
                                <td></td>
                                <td></td>
                                <td>2008</td>
                                <td>Cuba</td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Hà Nam</td>
                                <td>Trạm 110kV Kim Bảng</td>
                                <td>MBAT5</td>
                                <td>Máy biến áp Test 1</td>
                                <td>UNINDO</td>
                                <td>Máy biến áp</td>
                                <td></td>
                                <td>2008</td>
                                <td>Cuba</td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Hà Nam</td>
                                <td>Trạm 110kV Kim Bảng</td>
                                <td>MBAT5</td>
                                <td>Máy biến áp Test 1</td>
                                <td>UNINDO</td>
                                <td>Máy biến áp</td>
                                <td></td>
                                <td>2008</td>
                                <td>Cuba</td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Hà Nam</td>
                                <td>Trạm 110kV Kim Bảng</td>
                                <td>MBAT5</td>
                                <td>Máy biến áp Test 1</td>
                                <td>UNINDO</td>
                                <td>Máy biến áp</td>
                                <td></td>
                                <td>2008</td>
                                <td>Cuba</td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="mr-1">
                                    09/07/2021
                                </td>
                                <td>PC Hà Nam</td>
                                <td>Trạm 110kV Kim Bảng</td>
                                <td>MBAT5</td>
                                <td>Máy biến áp Test 1</td>
                                <td>UNINDO</td>
                                <td>Máy biến áp</td>
                                <td></td>
                                <td>2008</td>
                                <td>Cuba</td>
                            </tr>
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

                if (totalChecked > 5) {
                    $(this).prop('checked', false);
                    alert("Chỉ được chọn tối đa 5 báo cáo");
                }
                if (totalChecked === 0) {
                    $('#btnViewReport').attr('disabled', 'disabled')
                } else {
                    $('#btnViewReport').removeAttr('disabled')
                }
            });
        });

        function gotoReport() {
            let viewContent = document.getElementById('ViewReport').value;

            if (viewContent == 1) {

                window.location.href = '{{ route('admin.analyticEmbed')}}';
            }
            if (viewContent == 2) {
                window.location.href = '{{route('admin.analyticEmbed_2')}}';
            }
        }
    </script>
@endpush
