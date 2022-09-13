@extends('layouts.master')

@section('pageTitle', 'BÁO CÁO PHÂN TÍCH PHÒNG HÓA DẦU')
@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo phân tích</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Phòng hóa dầu</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px" >
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Quản lý báo cáo phân tích OLTC</h4>
        </div>
    </div>
    <div class="btn-download" style="text-align: right;">
        <a href="{{ route('adminoltcAnalytic_export', $id) }}" class="btn btn-primary" style="margin-bottom: 15px;">Xuất File</a>
    </div>
    <div class="card">
        <div class="card-body min_table list_list w-100 vh-100">
             <iframe src='https://view.officeapps.live.com/op/embed.aspx?src={{ $excel }}?t={{ time() }}' width='100%' height="100%" frameborder='0'> </iframe>
        </div>
    </div>
@endsection
