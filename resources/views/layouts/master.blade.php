<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Công ty TNHH MTV thí nghiệm điện Miền Bắc, Hệ thống báo cáo - Phần mềm Hệ thống Cơ sở dữ liệu lưới điện">
    <meta name="keywords" content="admin, admin panel, admin template, admin dashboard, responsive, bootstrap 4, ui kits, ecommerce, web app, crm, cms, html, sass support, scss">
    <meta name="author" content="Themesbox">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>@yield('pageTitle')</title>
    <!-- Fevicon -->
    <link rel="shortcut icon" href="{{ asset('theme/assets/images/favicon.png') }}">
    <!-- Start css -->
    <!-- Switchery css -->
    <link href="{{ asset('theme/assets/plugins/switchery/switchery.min.css') }}" rel="stylesheet">
    <!-- Pnotify css -->
    <link href="{{ asset('theme/assets/plugins/pnotify/css/pnotify.custom.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/plugins/datepicker/datepicker.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/icons.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/flag-icon.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/style.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/evn.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/assets/css/custom.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}" />
    @stack('css')
    <!-- End css -->
</head>
<body class="vertical-layout horizontal-layout">
<!-- Start Infobar Setting Sidebar -->

<div class="infobar-settings-sidebar-overlay"></div>
<!-- End Infobar Setting Sidebar -->
<!-- Start Containerbar -->
<div id="containerbar">
    <!-- Start Leftbar -->
    <div class="leftbar">
        <!-- Start Sidebar -->
        @include('shared.left_sidebar')
        <!-- End Sidebar -->
    </div>
    <!-- End Leftbar -->
    <!-- Start Rightbar -->
    <div class="rightbar">
        <!-- Start Topbar Mobile -->

        <!-- Start Topbar -->
        @include('shared.topbar')
        <!-- End Topbar -->
        @if (session()->has('unauthorized'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 65px;">
            {{ session()->get('unauthorized') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif
        <!-- Start Breadcrumbbar -->
        @yield('breadcumb')
        <!-- End Breadcrumbbar -->
        <!-- Start Contentbar -->
        <div id="mainContent" class="contentbar">
            <!-- Start row -->
            @yield('content')
            <!-- End row -->
        </div>
        <div class="masterLoadingBox" style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0; z-index: -1;">
            <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px; position: absolute; top: 37%; left: 53.3%;">
        </div>
        <!-- End Contentbar -->
        <!-- Start Footerbar -->
        <div class="footerbar">
            <footer class="footer">
                <p class="mb-0">© 2020 EVN - All Rights Reserved.</p>
            </footer>
        </div>
        <!-- End Footerbar -->
    </div>
    <!-- End Rightbar -->
</div>
<!-- End Containerbar -->
<!-- Start js -->
<script src="{{ asset('theme/assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/popper.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/modernizr.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/detect.js') }}"></script>
<script src="{{ asset('theme/assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ asset('theme/assets/js/vertical-menu.js') }}"></script>
<script src="{{ asset('theme/assets/js/horizontal-menu.js') }}"></script>
<!-- Switchery js -->
<script src="{{ asset('theme/assets/plugins/switchery/switchery.min.js') }}"></script>
<script src="{{ asset('theme/assets/plugins/select2/select2.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/custom/custom-form-select.js') }}"></script>
<!-- Datepicker JS -->
<script src="{{ asset('theme/assets/plugins/datepicker/datepicker.min.js') }}"></script>
<script src="{{ asset('theme/assets/plugins/datepicker/i18n/datepicker.en.js') }}"></script>
<script src="{{ asset('theme/assets/js/custom/custom-form-datepicker.js') }}"></script>
<!-- Pnotify js -->
<script src="{{ asset('theme/assets/plugins/pnotify/js/pnotify.custom.min.js') }}"></script>
<script src="{{ asset('theme/assets/js/custom/custom-pnotify.js') }}"></script>>
<!-- Core js -->
<script src="{{ asset('theme/assets/js/core.js') }}"></script>
<script>
    @if (isset($request['series']))
        let activeMenu = true;
    @endif
</script>
<script src="{{ asset('theme/assets/js/custom.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/toastr.min.js') }}"></script>

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "showDuration": "3000",
        "hideDuration": "5000",
        "timeOut": "5000",
        "extendedTimeOut": "5000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
</script>
<script>
    $(function(){
        setTimeout(function(){
            if($('#child-menu').hasClass('active-current')){
                $('#toggle-menu').addClass('clicked');
            }
        }, 0);
    });
    $('#manager-device').click(function(e){
        $('#report-statistical').parent().removeClass('active').find('> .vertical-submenu').hide();
        $('#report-analysis').parent().removeClass('active').find('> .vertical-submenu').hide();
        $('.masterLoadingBox').css({
            'opacity': '.5',
            'z-index': '1'
        });
        e.preventDefault();
        $('a').removeClass('active active-current');
        $(this).addClass('active-current');
        $(this).parent().toggleClass('active');
        if($(this).parent().hasClass('active')){
            $(this).parent().find(' > .vertical-submenu').fadeIn('slow');
        }
        $.ajax({
            url: '{{ route("admin.getViewDevice") }}',
            method: "GET",
            data: {},
            dataType: 'html',
        }).done(function(result) {
            $('.masterLoadingBox').css({
                'opacity': '0',
                'z-index': '-1'
            });
            let response = JSON.parse(result);
            if (typeof response['error'] !== 'undefined') {
                for (let i = 0; i < response['error'].length; i++) {
                    toastr["error"](response['error'][i]);
                }
            }
            if (typeof response['success'] !== 'undefined') {
                $('#mainContent').html(response['html']);
                $('.vertical-menu.in > li:not(.tree_menu_sub)').removeClass('active');
                $('.rightbar > .breadcrumbbar').hide();
            }
        });
    });
    $('.tree_menu_sub .vertical-submenu a').on('click', function(e){
        e.preventDefault();
        let ids = $(this).data('id').toString();
        // console.log(ids);
        ids = ids.split('_');
        const group = ids[0];
        const dvql = ids[1] ?? '';
        const td = ids[2] ?? '';
        const nl = ids[3] ?? '';

        let data = $('#search-form').serialize();
        let dataArr = data.split('&');

        if(dvql != ''){
            $('#dvql_form').val(dvql);
            $('#dvql_form').change();
            dataArr[6] = "dvql_form=" + dvql;
        }

        if(td != ''){
            $('#td_form').val(td);
            $('#td_form').change();
            dataArr[7] = "td_form=" + td;
        }else{
            dataArr[7] = "td_form=";
        }

        if(nl != ''){
            dataArr[8] = "nl_form=" + nl;
        }else{
            dataArr[8] = "nl_form=";
        }
        data = dataArr.join('&');

        if(dvql != '' || nl != '' || td != ''){
            $.ajax({
                url: '{{ route("admin.ajaxFilterDeviceModules") }}',
                method: "GET",
                data: data,
                dataType: 'html',
            }).done(function(result) {
                setTimeout(() => {
                    $('#dvql_form').val(dvql);
                    $('#td_form').val(td);
                    $('#nl_form').val(nl);
                }, 1000);
                $('.masterLoadingBox').css({
                    'opacity': '0',
                    'z-index': '-1'
                });
                let response = JSON.parse(result);
                if (typeof response['error'] !== 'undefined') {
                    for (let i = 0; i < response['error'].length; i++) {
                        toastr["error"](response['error'][i]);
                    }
                }
                if (typeof response['success'] !== 'undefined') {
                    $('.wrapper-device').html(response['html']);
                }
            });
        }
    });

    $('.tree_menu_sub a').on('click', function(e){
        e.preventDefault();
        localStorage.setItem('device-id', $(this).data('id'));
    });

    $('.navigationbar a').on('click', function(){
        if( !$(this).hasClass('logo') ){
            $('.navigationbar a').removeClass('active active-current');
            $(this).addClass('active-current');
        }
    });
    $('.reference-device').on('click', function(e){
        e.preventDefault();
        $('#manager-device').click();
    });
    $('.reference-analysis').on('click', function(e){
        e.preventDefault();
        $('#report-analysis').click();
    });
    $('.reference-statistical').on('click', function(e){
        e.preventDefault();
        $('#report-statistical').click();
    });

    $('#toggle-menu').on('click', function(){
        $(this).toggleClass('clicked');
        $(this).parent().find('>.vertical-submenu').toggle('fast');
    });
</script>
@stack('scripts')
<!-- End js -->
</body>
</html>
