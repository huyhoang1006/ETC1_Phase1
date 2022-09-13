<div class="topbar ">
    <!-- Start row -->
    <div class="row align-items-center">
        <!-- Start col -->
        <div class="col-md-12 align-self-center">
            <div class="togglebar">
                <!-- Horizonatal menu-->
            <div class="horizontal_navigationbar">
                <!-- Start container-fluid -->
                <div class="container-fluid">
                    <!-- Start Horizontal Nav -->
                    <nav class="horizontal-nav mobile-navbar fixed-navbar">
                        <div class="collapse navbar-collapse" id="navbar-menu">
                          <ul class="horizontal-menu">
                            <li class="scroll dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-gear"></i><span>Hệ thống</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="{{ route('admin.dashboard.index') }}">- Trang chủ</a></li>
                                    <li><a href="#" class="reference-device">- Chọn đơn vị quản lý</a></li>
                                    <li><a href="{{ env('CA_URL') }}">- Quản lý biên bản</a></li>
                                    <li><a href="#" class="reference-device">- Quản lý thiết bị</a></li>
                                    <li><a href="#">- Quản lý thông báo</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-gears"></i><span>Thiết bị</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="{{ env('CA_URL') }}">- Làm biên bản</a></li>
                                    <li><a href="#" class="reference-analysis">- Báo cáo phân tích</a></li>
                                    <li><a href="#" class="reference-statistical">- Báo cáo thống kê</a></li>
                                    <li><a href="dashboard-ecommerce.html">- Tìm kiếm</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-truck"></i><span>Thông số vận hành</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard-hospital.html">- Bảng nhập thông số vận hành</a></li>
                                    <li><a href="dashboard-hospital.html">- Giám sát thông số thiết bị</a></li>
                                </ul>
                            </li>    
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-exclamation-circle"></i><span>Sự cố</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard-hospital.html">- Thông số sự cố</a></li>
                                    <li><a href="dashboard-hospital.html">- Khắc phục sự cố</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-edit"></i><span>Công việc</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard-hospital.html">- Tra cứu</a></li>
                                    <li><a href="dashboard-hospital.html">- Kết quả thực hiện</a></li>
                                    <li><a href="dashboard-hospital.html">- Thiết lập công việc</a></li>
                                    <li><a href="dashboard-hospital.html">- Công tác</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-copy"></i><span>CBM</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard-hospital.html">- Thiết lập CBM</a></li>
                                    <li><a href="dashboard-hospital.html">- Thiết lập CBM</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="javaScript:void();" class="dropdown-toggle" data-toggle="dropdown"><i class="la la-paste"></i><span>Báo cáo</span><i class="fa fa-sort-down font-12"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a href="dashboard-hospital.html">- Báo cáo nội bộ</a></li>
                                    <li><a href="dashboard-hospital.html">- Báo cáo NPC SVN</a></li>
                                </ul>
                            </li>
                          </ul>
                        </div>
                    </nav>
                    <!-- End Horizontal Nav -->
                </div>
                <!-- End container-fluid -->
            </div>
            <!--End  Horizonatal menu-->
            </div>
            <div class="infobar">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <div class="profilebar">
                            <div class="dropdown">
                                <a class="dropdown-toggle" href="#" role="button" id="profilelink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="{{ asset('theme/assets/images/users/profile.svg') }}" class="img-fluid" alt="profile"><span class="live-icon">{{ @session()->get(env('AUTH_SESSION_KEY'))['username'] }}</span><span class="feather icon-chevron-down live-icon"></span></a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profilelink">
                                    <div class="userbox">
                                        <ul class="list-unstyled mb-0">
                                            <li class="media dropdown-item">
                                                <a href="{{ route('admin.auth.logout') }}" class="profile-icon"><img src="{{ asset('theme/assets/images/svg-icon/logout.svg') }}" class="img-fluid" alt="logout">Đăng xuất</a>
                                            </li>
                                            @php
                                                $user = session()->get(env('AUTH_SESSION_KEY'));
                                                $role = @$user['role'];
                                                $permissions = @$user['permissions'] ?? [];
                                            @endphp
                                            @if ($role == \App\Http\Middleware\PermissionCustom::IS_ADMIN)
                                            <li class="media dropdown-item">
                                                <a href="{{ route('admin.permission') }}">Phân quyền</a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <!-- End col -->
    </div>
    <!-- End row -->
</div>
