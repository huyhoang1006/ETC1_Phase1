@extends('layouts.master')

@section('pageTitle', 'Báo cáo CNNL')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="javaScript:void();">Phòng công nghệ năng lượng</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12 mb-5 d-flex justify-content-between">
            <h4 class="text-dark">Báo cáo kết quả đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn nhiệt độ</h4>
            <a href="{{ $excel }}" class="float-right btn btn-success" download>
                <i class="fas fa-download"></i>
                Xuất File
            </a>
        </div>
        <div class="col-lg-12 col-xl-12" style="height: 100vh">
            <iframe src='https://view.officeapps.live.com/op/embed.aspx?src={{ $excel }}?t={{ time() }}' width='100%' height="100%" frameborder='0'> </iframe>
        </div>
    </div>
@endsection
