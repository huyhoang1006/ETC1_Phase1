@extends('layouts.master')

@section('pageTitle', 'Báo cáo cao áp')


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
            <h4 class="m-b-30">Báo cáo kiểm tra bên ngoài</h4>
        </div>
        <div class="col-lg-12 col-xl-12" style="height: 100vh">
            <iframe src='https://view.officeapps.live.com/op/embed.aspx?src=http://telehouse.npcetc.paditech.org/demo/caoap_demo.xlsx' width='100%' height="100%" frameborder='0'> </iframe>
        </div>
    </div>
@endsection
