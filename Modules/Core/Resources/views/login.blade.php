<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Công ty TNHH MTV thí nghiệm điện Miền Bắc - Hệ thống báo cáo - Phần mềm Hệ thống Cơ sở dữ liệu lưới điện">
    <meta name="keywords" content="admin, admin panel, admin template, admin dashboard, responsive, bootstrap 4, ui kits, ecommerce, web app, crm, cms, html, sass support, scss">
    <meta name="author" content="Themesbox">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>Login</title>
    <!-- Fevicon -->
    <link rel="shortcut icon" href="{{ asset('theme/assets/images/favicon.png') }}">
    <!-- Start css -->
    <link href="{{ asset('theme/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/icons.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/style.css') }}" rel="stylesheet" type="text/css">
    <!-- End css -->
</head>
<body class="vertical-layout">
    @php
        $info = session('info_user') ?? [];
    @endphp
<!-- Start Containerbar -->
<div id="containerbar" class="containerbar authenticate-bg">
    <!-- Start Container -->
    <div class="container">
        <div class="auth-box login-box">
            <!-- Start row -->
            <div class="row no-gutters align-items-center justify-content-center">
                <!-- Start col -->
                <div class="col-md-6 col-lg-5">
                    <!-- Start Auth Box -->
                    <div class="auth-box-right">
                        <div class="card">
                            <div class="card-body">
                                <form method="post" action="{{ route('admin.auth.login') }}" class="pb-3">
                                    {{ csrf_field() }}
                                    <div class="form-head">
                                        <a href="/" class="logo"><img src="{{ asset('theme/assets/images/logo.png') }}" class="img-fluid" alt="logo"></a>
                                    </div>
                                    <div class="login_note">
                                        <p>Công ty TNHH MTV thí nghiệm điện Miền Bắc</p>
                                        <p>Hệ thống báo cáo - Phần mềm Hệ thống Cơ sở dữ liệu lưới điện</p>
                                    </div>
                                    <h4 class="text-primary my-4">Đăng nhập !</h4>
                                    <div class="form-group">
                                        <input type="text" name="username" value="{{ @$info['remember'] ? $info['username'] : '' }}" class="form-control" id="username" placeholder="Tên đăng nhập" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" name="password" value="{{ @$info['remember'] ? $info['password'] : '' }}" class="form-control" id="password" placeholder="Mật khẩu" required>
                                    </div>
                                    <div class="form-row mb-3">
                                        <div class="col-sm-6">
                                            <div class="custom-control custom-checkbox text-left">
                                                <input name="remember" type="checkbox" class="custom-control-input" id="rememberme" {{ @$info['remember'] == true ? "checked" : '' }}>
                                                <label class="custom-control-label font-14" for="rememberme">Nhớ tài khoản</label>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($errors->any())
                                        <div class="mb-2">
                                            @foreach($errors->all() as $error)
                                                <div style="color: red">{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(session()->has('login_error'))
                                        <div class="mb-2" style="color:red">{{session()->get('login_error')}}</div>
                                    @endif
                                    @if(session()->has('ca_code'))
                                        <script>
                                            const code = '{{ session()->get("ca_code") }}';
                                            const message = '{{ session()->get("ca_message") }}';
                                            setTimeout(() => {
                                                alert(`Mã lỗi: ${code} - Message: ${message}`);
                                            }, 0);
                                        </script>
                                    @endif
                                    <button type="submit" class="btn btn-success btn-lg btn-block font-18">Đăng nhập</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End Auth Box -->
                </div>
                <!-- End col -->
            </div>
            <!-- End row -->
        </div>
    </div>
    <!-- End Container -->
</div>
<!-- End Containerbar -->
<!-- Start js -->
<script src="{{ asset('theme/assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/popper.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/modernizr.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/detect.js') }}"></script>
<script src="{{ asset('theme/assets/js/jquery.slimscroll.js') }}"></script>
<!-- End js -->
</body>
</html>
