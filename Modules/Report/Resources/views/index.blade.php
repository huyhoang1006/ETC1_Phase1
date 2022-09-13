@extends('layouts.master')

@section('pageTitle', 'BÁO CÁO THỐNG KÊ')
@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Báo cáo thống kê</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index list-report">
{{--        <div class="col-lg-12 col-xl-12">--}}
{{--            <h4 class="m-b-30">Báo cáo thống kê</h4>--}}
{{--        </div>--}}
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">PX Cơ điện</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">PTN Tự động hóa</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">PTN Rơ le</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">PTN Hoa</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">Đo lường</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">CNNL</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
        <!-- Start col -->
        <div class="col-lg-4 col-xl-2">
            <div class="card m-b-30">
                <a href="#">
                    <div class="card-body">
                        <div class="pnotify text-center">
                            <button type="button" class="btn btn-primary" id="pnotify-primary"><i class="ti-pencil-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="card-title text-center">Cao áp</h5>
                    </div>
                </a>
            </div>
        </div>
        <!-- End col -->
    </div>
@endsection
