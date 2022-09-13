@extends('layouts.master')
@section('pageTitle', 'QUẢN LÝ THIẾT BỊ')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.device.index') }}">Quản lý thiết bị</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ @$item['name'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="contentbar">
        <!-- Start row -->
        <div class="row detail">
            <div class="col-xl-12 m-b-20">
                <h3 class="text-center">{{ @$item['name'] }}</h3>
            </div>
            <div class="col-xl-12 m-b-20">
                <div class="row">
                    <div class="col-sm-12 col-xl-6">
                        <div class="card m-b-30">
                            <div class="card-body">
                                @if ( file_exists(public_path('theme/assets/images/device/' . $item['class'] . '.png')) )
                                    <img src="{{ asset('theme/assets/images/device/' . $item['class'] . '.png') }}"
                                    alt="Thumbnail Image" class="rounded mx-auto d-block" style="max-width: 100%;">
                                @else
                                    <img src="{{ asset('theme/assets/images/device/no-image.png') }}"
                                        alt="Thumbnail Image" class="rounded mx-auto d-block" style="max-width: 100%;">
                                @endif
                            </div>
                        </div>
                        @if (!empty($label) && !empty($attrs))
                            <div class="card m-b-30">
                                <div class="card-header">
                                    <h5 class="card-title">Bảng các thông tin riêng</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="thead-blue">
                                                <tr>
                                                    <th>STT</th>
                                                    <th>Thông tin hiển thị</th>
                                                    <th>Nội dung</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($label as $index => $title)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ $title }}</td>
                                                        @if ($title == 'Phiếu chỉnh định')
                                                            <td> {{ @$item['zphieuchinhdinh'] }} </td>
                                                            </td>
                                                        @else
                                                            <td> {{ @$attrs[$index] && strpos(strtolower($attrs[$index]), 'date') ? ( @$infoSingle[$attrs[$index]] ? date('d/m/Y', $infoSingle[$attrs[$index]]) : '' ) : @$infoSingle[$attrs[$index]] }} </td>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                @endforeach
                                                @if ( !empty($lableDevice) && !empty($attrsDevice) )
                                                    @foreach ($lableDevice as $index => $label)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ $label }}</td>
                                                        <td>{{ $item[$attrsDevice[$index]] ?? ''}}
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                    @endforeach
                                                @endif
                                                @if ( !empty($labelReport) && !empty($attributesReport) )
                                                    @foreach ($labelReport as $index => $label)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ $label }}</td>
                                                        <td>{{ $assocReport[$attributesReport[$index]] ?? ''}}
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-12 col-xl-6">
                        <div class="card m-b-30">
                            <div class="card-header">
                                <h5 class="card-title">Bảng thông tin chung</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="thead-blue">
                                            <tr>
                                                <th>STT</th>
                                                <th>Thông tin hiển thị</th>
                                                <th>Nội dung</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>Đơn vị quản lý <br /> (Customer)</td>
                                                <td>{{ @$item['zrefnr_dvql.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>Khu vực (Tỉnh/Thành phố) <br /> (Area)</td>
                                                <td>{{ @$item['zArea.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>3</td>
                                                <td>Trạm/Nhà máy <br />(Substation/Plant)</td>
                                                <td>{{ @$item['zrefnr_td.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>4</td>
                                                <td>Ngăn lộ/Hệ thống/Tổ máy <br /> (Bay/System/Unit):</td>
                                                <td>{{ @$item['zrefnr_nl.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>5</td>
                                                <td>Thiết bị <br /> (Equipment)</td>
                                                <td>{{ @$item['name'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>6</td>
                                                <td>Chủng loại <br /> (Type)</td>
                                                <td>{{ @$item['zCI_Device_Type.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>7</td>
                                                <td>Hãng sản xuất <br /> (Manufacturer)</td>
                                                <td>{!! @$item['zManufacturer.zsym'] !!}</td>
                                            </tr>
                                            <tr>
                                                <td>8</td>
                                                <td>Kiểu thiết bị <br /> (Model)</td>
                                                <td>{{ @$item['zCI_Device_Kind.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>9</td>
                                                <td>Số chế tạo <br />
                                                    (Serial Number)
                                                </td>
                                                <td>{{ @$item['serial_number'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>10</td>
                                                <td>Năm sản xuất <br />
                                                    (Year of Manufacture)
                                                </td>
                                                <td>{{ @$item['zYear_of_Manafacture.zsym'] }}</td>
                                            </tr>
                                            <tr>
                                                <td>11</td>
                                                <td>Nước sản xuất <br />
                                                    (Country/Origin)</td>
                                                <td>{{ @$item['zCountry.name'] }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-xl-12">
                <div class="card m-b-30">
                    <div class="card-header">
                        <h5 class="card-title">Bảng theo dõi công tác thí nghiệm</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-blue">
                                    <tr>
                                        <th>STT</th>
                                        <th>Loại hình TN</th>
                                        <th>Người thực hiện</th>
                                        <th>Ngày thực hiện</th>
                                        <th>Kết quả TN</th>
                                        <th>Biên bản</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!count($experiments))
                                        <tr>
                                            <td colspan="6" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    @endif
                                    @foreach ($experiments as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ @$row['ztestType.zsym'] }}</td>
                                            <td>{{ implode(' ', [@$row['zExperimenter.last_name'], @$row['zExperimenter.middle_name'], @$row['zExperimenter.first_name']]) }}
                                            </td>
                                            <td>{{ date('Y-m-d H:i:s', @$row['creation_date']) }}</td>
                                            <td>{{ @$row['zresultEnd'] }}</td>
                                            <td>{{ @$row['class.type'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        const id = localStorage.getItem('device-id') ?? '';
        if ( id != '' ){
            setTimeout(function(){
                $('a').removeClass('active-current');
                $('#v-pills-uikits > .vertical-menu > li:not(.tree_menu_sub)').removeClass('active');
                $('#v-pills-uikits > .vertical-menu > li:not(.tree_menu_sub) > .vertical-submenu').hide();
                $(`.tree_menu_sub a[data-id="${id}"]`).addClass('active-current');
                $(`.tree_menu_sub a[data-id="${id}"]`).parent().addClass('active');
                $(`.tree_menu_sub a[data-id="${id}"]`).parent().parent().parent().addClass('active');
                $(`.tree_menu_sub a[data-id="${id}"]`).parent().parent().parent().parent().parent().addClass('active');
                $(`.tree_menu_sub a[data-id="${id}"]`).parent().parent().parent().parent().parent().parent().parent().addClass('active');
                $(`.tree_menu_sub a[data-id="${id}"]`).parent().parent().parent().parent().parent().parent().parent().parent().parent().addClass('active');
            }, 200);
        }
        localStorage.setItem('mode', 'detail');
        $('.tree_menu_sub .vertical-submenu a').on('click', function(e){
            let mode = localStorage.getItem('mode');
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
                if( mode == 'detail'){
                    $.ajax({
                        url: '{{ route("admin.getViewDevice") }}',
                        method: "GET",
                        data: data,
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
                            localStorage.setItem('mode', 'sidebar');
                        }
                    });
                }else{
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
            }
        });
    </script>
@endpush
