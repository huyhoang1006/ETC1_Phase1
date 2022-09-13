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
                        <li class="breadcrumb-item active" aria-current="page">{{$type_report??'Máy cắt'}}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12 mb-5 d-flex justify-content-between">
            <h4 class="text-dark title-left">{{$title??''}}</h4>
            <a href="{{$excel??''}}">
                <button class="float-right btn btn-success" data-toggle="tooltip" data-placement="top" title="Xuất file .xlsx">
                    <i class="fas fa-download"></i>
                    Xuất File
                </button>
            </a>
        </div>

        <div class="col-lg-12 col-xl-12" style="height: 100vh">
            <iframe src='{{$link_preview??''}}' width='100%' height="100%" frameborder='0'> </iframe>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        let count = localStorage.getItem('back') ?? 0;
        if( count ){
            localStorage.setItem("back", ++count);
        }
    </script>
@endpush