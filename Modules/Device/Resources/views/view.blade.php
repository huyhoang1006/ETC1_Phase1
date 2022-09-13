

<div class="breadcrumbbar">
    <div class="row align-items-center">
        <div class="col-md-12">
            <div class="breadcrumb-list">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý thiết bị</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div>
    <div class="row align-items-center m-b-20">
        <div class="col-md-8 col-lg-8">
            <h4 class="page-title">Danh sách thiết bị thí nghiệm hiệu chỉnh</h4>
        </div>
    </div>
    <div class="row dashboard-index detail-report">
        <div class="col-lg-12">
            <div class=" m-b-30">
                <div class="card m-b-30">
                    <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                        <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                    </div>
                    <form class="card-body module_search" method="get" action="{{ route('admin.device.index') }}" id="search-form" style="position: relative;">
                        <input type="hidden" name="nl" value="{{ request()->get('nl') }}">
                        <div class="form-group form_input">
                            <input type="text" class="form-control" name="name" value="{{ request()->get('name') }}" id="inputText" placeholder="Tìm kiếm theo tên">
                        </div>
                        <div class="form-group form_input">
                            <input type="text" class="form-control" name="series" value="{{ request()->get('series') }}" id="inputText" placeholder="Tìm kiếm theo Serial">
                        </div>
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="manufacture" id="manufacture1">
                                <option value="">Hãng sản xuất</option>
                                @foreach($manufactures as $manufacture)
                                    <option {{ request()->get('manufacture') == $manufacture['zsym'] ? 'selected' : '' }} value="{!! $manufacture['zsym'] !!}">{!! $manufacture['zsym'] !!}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="year" id="year">
                                <option value="">Năm sản xuất</option>
                                @foreach($years as $year)
                                    <option {{ request()->get('year') == $year['id'] ? 'selected' : '' }} value="{{ $year['id'] }}">{{ $year['zsym'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ( !request()->get('nl') && !request()->get('td') && !request()->get('dvql'))
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="city" id="city">
                                <option value="">Khu vực</option>
                                @foreach($cities as $city)
                                    <option {{ request()->get('city') == $city['id'] ? 'selected' : '' }} value="{{ $city['id'] }}">{{ $city['zsym'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="dvql_form" id="dvql_form">
                                <option value="">Đơn vị quản lý</option>
                                @foreach($dvqls as $dvql)
                                    <option {{ request()->get('dvql_form') == $dvql['id'] ? 'selected' : '' }} value="{{ $dvql['id'] }}">{!! $dvql['zsym'] !!}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="td_form" id="td_form">
                                <option value="">Trạm/Nhà máy</option>
                            </select>
                        </div>
                        <div class="form-group form_input form_type">
                            <select class="select2-single form-control" name="nl_form" id="nl_form">
                                <option value="">Ngăn lộ/Hệ thống</option>
                            </select>
                        </div>
                        @endif
                        <div class="form-group form_submit">
                            <button type="submit" class="btn btn-dark" id="btn-submit">Tìm kiếm</button>
                        </div>
                    </form>
                </div>
                <div id="device-content">
                    <div class="wrapper-device">
                        @include('device::data')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loadingBox2" style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;background: #fff;justify-content: center;display: flex;align-items: flex-start;opacity: 0; z-index: -1;">
    <img src="{{ asset('images/loading.gif') }}" alt="" class="img-fluid" style="width: 50px;margin-top: 50px; position: absolute; top: 98%; left: 45%;">
</div>
<input type="hidden" id="errors" value="{{ json_encode($errors->all()) }}">
<style>
    .loadingBox2 img{
        top: 440px !important;
        left: 55% !important;
    }
    .loadingBox img{
        top: 440px !important;
        left: 55% !important;
    }
</style>
<script>
    function ajaxGetSelect(id, url, optionBlack, elementAppend){
        $(elementAppend).attr('disabled', 'disabled');
        $.ajax({
            url: url,
            method: "GET",
            data: { id },
            dataType: 'html',
        }).done(function(result) {
            $(elementAppend).removeAttr('disabled');
            let response = JSON.parse(result);
            if (typeof response['error'] !== 'undefined') {
                for (let i = 0; i < response['error'].length; i++) {
                    toastr["error"](response['error'][i]);
                }
            }
            if (typeof response['success'] !== 'undefined') {
                let html = `<option value="">${optionBlack}</option>`;
                $.each(response['data'], function(index, value){
                    html += `<option value=${value['id']}>${value['zsym']}</option>`;
                });
                $(elementAppend).html(html);
            }
        });
    }
    $('#dvql_form').on('change', function(){
        $('#nl_form').html('<option value="">Ngăn lộ/Hệ thống</option>');
        const id = $(this).val();
        const url = '{{ route("admin.getTD") }}';
        const urlNL = '{{ route("admin.getNL") }}';
        const optionBlack = 'Trạm/Nhà máy';
        const optionBlackNL = 'Ngăn lộ/Hệ thống';
        const elementAppend = '#td_form';
        const elementAppendNL = '#nl_form';
        ajaxGetSelect(id, url, optionBlack, elementAppend);
    });
    $('#td_form').on('change', function(){
        const id = $(this).val();
        const url = '{{ route("admin.getNL") }}';
        const optionBlack = 'Ngăn lộ/Hệ thống';
        const elementAppend = '#nl_form';
        ajaxGetSelect(id, url, optionBlack, elementAppend);
    });
    function ajaxGetDevice(data = {}){
        $('.loadingBox2').css({
            'opacity': '.5',
            'z-index': '1'
        });
        $.ajax({
            url: '{{ route("admin.ajaxFilterDeviceModules") }}',
            method: "GET",
            data: data,
            dataType: 'html',
        }).done(function(result) {
            $('.loadingBox2').css({
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
                $('.wrapper-device').html(response['html'])
            }
        });
    };
    function getURLParameter(url, name) {
        return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
    }
    $("body").on("click", ".pagination li a", function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        const page = getURLParameter(url, 'page');
        const data = $('#search-form').serialize() + "&page=" + page;
        ajaxGetDevice(data);
    });
    $("body").on("click", "#btn-submit", function(e){
        e.preventDefault();
        ajaxGetDevice($('#search-form').serialize());
    });
</script>
