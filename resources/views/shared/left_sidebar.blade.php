@php
    $user = session()->get(env('AUTH_SESSION_KEY'));
    $role = @$user['role'];
    $permissions = @$user['permissions'] ?? [];
@endphp
<div class="sidebar">
    <!-- Start Navigationbar -->
    <div class="navigationbar">

        <div class="vertical-menu-detail">
            <div class="logobar">
                <a href="{{ route('admin.dashboard.index') }}" class="logo logo-large"><img
                        src="{{ asset('theme/assets/images/bglogo.png') }}" class="img-fluid" alt="logo"></a>
            </div>
            <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="v-pills-uikits" role="tabpanel"
                     aria-labelledby="v-pills-uikits-tab">
                    <ul class="vertical-menu">
                        <li>
                            <a href="{{ route('admin.dashboard.index') }}">
                                <i class="la la-home"></i><span>Trang chủ </span>
                            </a>
                        </li>
                        @if ( in_array(config('constant.permissions.petrochemical'), $permissions) || in_array(config('constant.permissions.high_pressure'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                        <li>
                            <a href="javaScript:void();" id="report-analysis">
                                <i class="la la-paste"></i><span>Báo cáo phân tích</span><i
                                    class="feather icon-chevron-right"></i>
                            </a>
                            <ul class="vertical-submenu">
                                @if ( in_array(config('constant.permissions.petrochemical'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">
                                        <span>Phòng TN Hóa</span>
                                        <i class="feather icon-chevron-right"></i>
                                    </a>
                                    <ul class="vertical-submenu">
                                        <li>
                                            <a href="{{ route('admin.report.1') }}">Báo cáo phân tích kết quả phân tích khí hoà tan trong dầu</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.oltcAnalytic' )}}">Báo cáo phân tích OLTC</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.high_pressure'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">
                                        <span>Phòng TN Cao Áp</span>
                                        <i class="feather icon-chevron-right"></i>
                                    </a>
                                    <ul class="vertical-submenu">
                                        <li class="link-has-child-menu">
                                            <a href="{{ route('admin.highPressure.transformers.index') }}">Máy biến áp</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.statisticalExperimental') }}">Thống kê công tác thí nghiệm</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressure.indexShare') }}">Máy cắt</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif
                        <li>
                            <a href="javascript:void();" id="report-statistical">
                                <i class="la la-paste"></i><span>Báo cáo thống kê</span>
                                <i class="feather icon-chevron-right" clas></i>
                            </a>
                            <ul class="vertical-submenu">
                                @if (in_array(config('constant.permissions.high_pressure'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">Phòng TN Cao Áp <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="{{ route('admin.statisticalListAndNumberDevice') }}">Báo cáo thống kê số lượng và danh sách thiết bị</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.experimentalResultsDevice') }}">Báo cáo thống kê kết quả thí nghiệm thiết bị</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'may-bien-ap-phan-phoi') }}">Báo cáo thống kê máy biến áp phân phối</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'ton-hao-may-bien-ap-phan-phoi') }}">Báo cáo thống kê chất lượng tổn hao MBA phân phối</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'chong-set-van') }}">Báo cáo thống kê chống sét van</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'may-bien-dong-dien') }}">Báo cáo thống kê thí nghiệm mẫu máy biến dòng điện</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'may-bien-dien-ap') }}">Báo cáo thống kê thí nghiệm mẫu máy biến điện áp</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'mau-cach-dien') }}">Báo cáo thống kê số lượng thí nghiệm mẫu cách điện</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.highPressureStatisticsReport', 'cap') }}">Báo cáo thống kê thí nghiệm mẫu cáp</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.energy'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="#">Phòng Công nghệ năng lượng <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="{{ route('admin.thiet_bi_hieu_chuan_ap_xuat') }}">Báo cáo kết quả đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn áp suất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.thiet_bi_hieu_chuan_nhiet_am_ke') }}">Báo cáo kết quả đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn nhiệt ẩm kế</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.thiet_bi_hieu_chuan_nhiet_do' )}}">Báo cáo kết quả đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn nhiệt độ</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.index', 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-tua-bin-hoi') }}">Báo cáo thống kê kết quả thí nghiệm thông số tuabin hơi </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.index', 'ket-qua-thi-nghiem-thong-so-tuabin-khi') }}">Báo cáo thống kê kết quả thí nghiệm thông số tuabin khí</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.index', 'bao-cao-danh-gia-ket-qua-thi-nghiem-do-dac-tuyen-to-may') }}">Báo cáo thống kê kết quả thí nghiệm tổ máy</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.index', 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-lon') }}">Báo cáo thống kê kết quả thí nghiệm thông số lò hơi lớn</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.index', 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-nho') }}">Báo cáo thống kê kết quả thí nghiệm thông số lò hơi công suất nhỏ</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.boilersByManufacture') }}">Báo cáo kết quả đánh gía hiệu suất nồi hơi công nghiệp tải dầu theo hãng sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.industrialFurnaceByManufacture') }}">Báo cáo đánh giá hiệu suất lò công nghiệp theo Hãng - nhiên liệu</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.measure'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">Phòng TN Đo lường <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="{{ route('admin.equipmentUnderInspection') }}">Báo cáo danh sách thiết bị theo hạn kiểm định</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.equipmentEveryYear') }}">Báo cáo số lượng thiết bị của từng năm sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.deviceListingReport') }}">Báo cáo thống kê danh sách thiết bị</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.incidentByCompany') }}">Báo cáo số lượng sự cố theo hãng</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.defectiveDevicesByManufacturer') }}">Báo cáo số lượng thiết bị sai số không đạt theo hãng sản xuất</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.petrochemical'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">Phòng TN Hóa <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="{{ route('admin.petrochemicalManufactures') }}">Báo cáo thống kê hãng sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.numberOfDevicesByManufactureReport') }}">Báo cáo thống kê số lượng thiết bị của từng hãng sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.quantityPercentageByManufacturerReport') }}">Báo cáo thống kê tỷ lệ theo số lượng của từng hãng sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.deviceReport') }}">Báo cáo tỉ lệ theo số lượng thiết bị của từng hãng sản xuất ứng với năm hoặc khoảng thời gian sản xuất</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.reportStatisticalPeriodicallyPetrochemical') }}">Báo cáo thống kê kết quả thí nghiệm định kỳ</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.reportExperimentalByRegion') }}">Báo cáo thống kê thí nghiệm theo khu vực</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.oilQualityReport') }}">Báo cáo chất lượng dầu</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.reportDissolvedGasOil') }}">Báo cáo khí hòa tan trong dầu</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.sf6GasQualityReport') }}">Báo cáo chất lượng khí SF6</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.electromechanical'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">Phân xưởng Cơ điện <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="javaScript:void();">Báo cáo thống kê cáp và dây dẫn đã thí nghiệm <i class="feather icon-chevron-right"></i></a>
                                            <ul class="vertical-submenu menu-open" style="display: none">
                                                <li>
                                                    <a href="{{ route('admin.conductorStatisticsReport') }}">Báo cáo tổng quan</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.monthlyReport') }}">Báo cáo theo tháng</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.quarterlyReport') }}">Báo cáo theo quý</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.annuallyReport') }}">Báo cáo theo năm</a>
                                                <li>
                                                    <a href="{{ route('admin.supplierQualityReport') }}">Báo cáo theo doanh số và chất lượng nhà cung cấp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.manufacturersSalesReport') }}">Báo cáo doanh số giữa các nhà sản xuất</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.supplierQualityComparisonReport') }}">Báo cáo so sánh chất lượng nhà cung cấp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.figuresForEachUnit') }}">Báo cáo theo đơn vị sử dụng - Bảng số liệu cho từng đơn vị</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.perUnitReport') }}">Báo cáo theo đơn vị sử dụng - bảng số liệu tổng hợp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.testResultsReport') }}">Báo cáo kết quả thí nghiệm mẫu cáp và dây dẫn đã thí nghiệm</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <a href="javaScript:void();">Báo cáo thí nghiệm thiết bị lẻ <i class="feather icon-chevron-right"></i></a>
                                            <ul class="vertical-submenu menu-open" style="display: none">
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Máy biến điện áp', 'type' => 'Máy biến điện áp trung áp']) }}">Báo cáo thí nghiệm, kiểm định máy biến điện áp trung áp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Máy biến dòng', 'type' => 'Máy biến dòng điện trung áp']) }}">Báo cáo thí nghiệm, kiểm định máy biến dòng điện trung áp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Máy biến dòng', 'type' => 'Máy biến dòng điện hạ áp']) }}">Báo cáo thí nghiệm, kiểm định máy biến dòng điện hạ áp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Voltmeter']) }}">Báo cáo thí nghiệm Voltmeter</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Ampemeter']) }}">Báo cáo thí nghiệm Ampemeter</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Máy cắt hạ thế']) }}">Báo cáo thí nghiệm máy cắt hạ thế</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Aptomat']) }}">Báo cáo thí nghiệm Aptomat các loại</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Contactor']) }}">Báo cáo thí nghiệm Contactor</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Rơ le nhiệt']) }}">Báo cáo thí nghiệm Rơle nhiệt</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Tụ điện', 'type' => 'Tụ điện hạ áp']) }}">Báo cáo thí nghiệm tụ điện hạ áp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Chống sét van', 'type' => 'Chống sét van hạ áp']) }}">Báo cáo thí nghiệm chống sét van hạ áp</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Sứ cách điện']) }}">Báo cáo thí nghiệm sứ cách điện</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Dụng cụ an toàn']) }}">Báo cáo thí nghiệm dụng cụ an toàn</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Công tơ', 'type' => 'Công tơ điện tử 1 pha']) }}">Báo cáo thí nghiệm công tơ điện tử 1 pha</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Công tơ', 'type' => 'Công tơ cảm ứng 1 pha']) }}">Báo cáo thí nghiệm công tơ cảm ứng 1 pha</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Công tơ', 'type' => 'Công tơ điện tử 3 pha']) }}">Báo cáo thí nghiệm công tơ điện tử 3 pha</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.deviceTestReport', ['class' => 'Công tơ', 'type' => 'Công tơ cảm ứng 3 pha']) }}">Báo cáo thí nghiệm công tơ cảm ứng 3 pha</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.numberOfExperimentsReport') }}">Báo cáo số lượng thí nghiệm trên từng loại thiết bị</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <a href="javaScript:void();">Báo cáo thí nghiệm nhất thứ <i class="feather icon-chevron-right"></i></a>
                                            <ul class="vertical-submenu menu-open" style="display: none">
                                                <li>
                                                    <a class="button-submenu" data-form="sub-menu" href="javaScript:void();">Máy cắt <i class="feather icon-chevron-right"></i></a>
                                                    <ul class="sub-menu" style="display:none;">
                                                        <li>
                                                            <a href="{{ route('admin.indexShare', ['title' => 'may-cat-bao-cao-so-luong-may-cat-trung-ap-da-thuc-hien']) }}">
                                                                Báo cáo số lượng máy cắt trung áp đã thí nghiệm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportTwo', ['title' => 'bao-cao-so-luong-may-cat-trung-ap-theo-cac-hang-san-xuat']) }}">
                                                                Báo cáo số lượng máy cắt trung áp theo các hãng sản xuất
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportThree', ['title' => 'may-cat-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat']) }}">
                                                                Báo cáo các hạng mục thí nghiệm máy cắt không đạt theo từng hãng sản xuất
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a class="button-submenu" data-form="sub-menu2" href="javaScript:void();">Máy biến dòng điện <i class="feather icon-chevron-right"></i></a>
                                                    <ul class="sub-menu2" style="display:none;">
                                                        <li>
                                                            <a href="{{ route('admin.indexShare', ['title' => 'may-bien-dong-dien-bao-cao-so-luong-da-thi-nghiem']) }}">
                                                                Báo cáo số lượng máy biến dòng điện đã thí nghiệm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportTwo', ['title' => 'bao-cao-so-luong-may-bien-dong-dien-theo-hang-san-xuat']) }}">
                                                                Báo cáo số lượng máy biến dòng điện theo các hãng sản xuất
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportThree', ['title' => 'may-bien-dong-dien-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat']) }}">
                                                                Báo cáo các hạng mục thí nghiệm máy biến dòng điện không đạt theo từng hãng sản xuất
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a class="button-submenu" data-form="sub-menu4" href="javaScript:void();">Cáp lực <i class="feather icon-chevron-right"></i></a>
                                                    <ul class="sub-menu4" style="display:none;">
                                                        <li>
                                                            <a href="{{ route('admin.indexShare', ['title' => 'cap-luc-bao-cao-so-luong-da-thi-nghiem']) }}">
                                                                Báo cáo số lượng cáp lực đã thí nghiệm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportTwo', ['title' => 'bao-cao-so-luong-cap-luc-theo-hang-san-xuat']) }}">
                                                                Báo cáo số lượng cáp lực theo các hãng sản xuất
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportThree', ['title' => 'cap-luc-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat']) }}">
                                                                Báo cáo các hạng mục thí nghiệm cáp lực  không đạt theo từng hãng sản xuất
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a class="button-submenu" data-form="sub-menu3" href="javaScript:void();">Máy biến áp phân phối <i class="feather icon-chevron-right"></i></a>
                                                    <ul class="sub-menu3" style="display:none;">
                                                        <li>
                                                            <a href="{{ route('admin.indexShare', ['title' => 'may-bien-ap-phan-phoi-bao-cao-so-luong-da-thi-nghiem']) }}">
                                                                Báo cáo số lượng máy biến áp phân phối đã thí nghiệm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportTwo', ['title' => 'bao-cao-so-luong-may-bien-ap-phan-phoi-theo-hang-san-xuat']) }}">
                                                                Báo cáo số lượng máy biến áp phân phối theo các hãng sản xuất
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportThree', ['title' => 'may-bien-ap-phan-phoi-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat']) }}">
                                                                Báo cáo các hạng mục thí nghiệm MBA tự phân phối (MBA tự dùng)  không đạt theo từng hãng sản xuất
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a  class="button-submenu" data-form="sub-menu5" href="javaScript:void();">Máy biến điện áp <i class="feather icon-chevron-right"></i></a>
                                                    <ul class="sub-menu5" style="display:none;">
                                                        <li>
                                                            <a href="{{ route('admin.indexShare', ['title' => 'may-bien-dien-ap-bao-cao-so-luong-da-thi-nghiem']) }}">
                                                                Báo cáo số lượng máy biến điện áp đã thí nghiệm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportTwo', ['title' => 'bao-cao-so-luong-may-bien-dien-ap-theo-hang-san-xuat']) }}">
                                                                Báo cáo số lượng máy biến điện áp theo các hãng sản xuất
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.indexShareReportThree', ['title' => 'may-bien-dien-ap-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat']) }}">
                                                                Báo cáo các hạng mục thí nghiệm máy biến điện áp không đạt theo từng hãng sản xuất
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <a href="javaScript:void();">Báo cáo thống kê hệ thống bảo vệ các ngăn lộ trung áp <i class="feather icon-chevron-right"></i></a>
                                            <ul class="vertical-submenu menu-open" style="display: none">
                                                <li>
                                                    <a href="{{ route('admin.protectionRelayReport') }}">Báo cáo số lượng Rơ le bảo vệ quá dòng theo các hãng lắp trên lưới điện trung áp trong tổng công ty</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.reportRelayFailureStatistics') }}">Báo cáo thống kê hư hỏng rơle</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.experimentalStatisticsReport') }}">Báo cáo thống kê công tác thí nghiệm</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.relay'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="javaScript:void();">Phòng TN Rơ le <i class="feather icon-chevron-right"></i></a>
                                    <ul class="vertical-submenu menu-open" style="display: none">
                                        <li>
                                            <a href="{{ route('admin.equipmentManufacturerStatisticsReport') }}">Báo cáo thống kê hãng thiết bị</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.reportDamagedRelay') }}">Báo cáo thống kê rơ le hư hỏng</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.reportRelayTestManifestSystem') }}">Báo cáo thống kê công tác thí nghiệm Rơ le</a>
                                        </li>
                                    </ul>
                                </li>
                                @endif
                                @if ( in_array(config('constant.permissions.automation'), $permissions) || $role == \App\Http\Middleware\PermissionCustom::IS_ADMIN )
                                <li>
                                    <a href="{{ route('admin.automation') }}">Phòng Tự động hóa</a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        <li class="tree_menu_sub">
                            <a href="javaScript:void();" id="manager-device" class="manager-device" data-id="parent">
                                <i class="la la-gears"></i><span>Quản lý thiết bị</span>
                                <i class="feather icon-chevron-right"></i>
                            </a>
                            @php
                                $service = new \App\Services\CAWebServices(env('CA_WSDL_URL'));
                                $user = session()->get(env('AUTH_SESSION_KEY'));
                                $treeMenu = $service->getCachedTreeMenu($user['ssid']);
                            @endphp
                            @foreach ($treeMenu as $zdisplay)
                            <ul class="vertical-submenu">
                                <li>
                                    <a href="javaScript:void();" data-id="{{ $zdisplay['id'] }}" data-type='group'>{{ $zdisplay['zsym'] }}<i class="feather icon-chevron-right"></i></a>
                                    @foreach($zdisplay['dvql'] as $dvql)
                                        <ul class="vertical-submenu">
                                            <li>
                                                @if ( !empty($dvql['td']) )
                                                    <a href="javaScript:void();" data-id="{{ $zdisplay['id'] }}_{{ $dvql['id'] }}" data-type='dvql'>{{ htmlspecialchars_decode($dvql['zsym']) }}<i class="feather icon-chevron-right"></i></a>
                                                    <ul class="vertical-submenu">
                                                        @foreach($dvql['td'] as $td)
                                                            <li>
                                                                @if ( !empty($td['nl']) )
                                                                    <a href="javaScript:void();" data-id="{{ $zdisplay['id'] }}_{{ $dvql['id'] }}_{{ $td['id'] }}" data-type='td'>{{ $td['zsym'] }}<i class="feather icon-chevron-right"></i></a>
                                                                    <ul class="vertical-submenu">
                                                                        @foreach ($td['nl'] as $nl)
                                                                        <li>
                                                                            <a href="{{ route('admin.device.index') }}?nl_form={{$nl['id']}}" data-id="{{ $zdisplay['id'] }}_{{ $dvql['id'] }}_{{ $td['id'] }}_{{ $nl['id'] }}" data-type='nl'>{{ $nl['zsym'] }}</a>
                                                                        </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @else
                                                                    <a href="{{ route('admin.device.index') }}?td_form={{$td['id']}}" data-id="{{ $zdisplay['id'] }}_{{ $dvql['id'] }}_{{ $td['id'] }}"  data-type='td'>{{ $td['zsym'] }}</a>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <a href="{{ route('admin.device.index') }}?dvql_form={{$dvql['id']}}" data-id="{{ $zdisplay['id'] }}_{{ $dvql['id'] }}" data-type='dvql'>{{ htmlspecialchars_decode($dvql['zsym']) }}</a>
                                                @endif
                                            </li>
                                        </ul>
                                    @endforeach
                                </li>
                            </ul>
                            @endforeach
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- End Navigationbar -->
</div>




