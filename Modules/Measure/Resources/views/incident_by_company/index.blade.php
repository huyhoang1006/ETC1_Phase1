@extends('layouts.master')
@section('pageTitle', 'BÁO CÁO THỐNG KÊ ĐO LƯỜNG')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="javaScript:void();">Báo cáo thống kê</a></li>
                        <li class="breadcrumb-item active"><a href="">Phòng đo lường</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('content')
    <div class="row dashboard-index detail-report" style="padding-top: 0px">
        <div class="col-lg-12 col-xl-12">
            <h4 class="m-b-30">Báo cáo số lượng sự cố theo hãng</h4>
        </div>
        <div class="col-sm-12 col-xl-12">
            <div class="card m-b-30">
                <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                    <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                </div>
                <form class="card-body module_search" style=" padding-top: 15px;padding-bottom: 20px;" method="get" action="{{ route('admin.incidentByCompany') }}">
                    <div class="form-group form_input">
                        <p>Loại thiết bị</p>
                        <select class="select2-single form-control" name="type_device">
                            <option value="">Loại thiết bị</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Công tơ') selected @endif value="Công tơ" >Công tơ</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Máy biến dòng') selected @endif value="Máy biến dòng">Máy biến dòng</option>
                            <option @if(!empty($request['type_device']) && $request['type_device'] == 'Máy biến điện áp') selected @endif value="Máy biến điện áp">Máy biến điện áp</option>
                        </select>
                    </div>
                    <div class="form-group form_submit mb-0" style="display: flex;align-items: center;width: 300px;">
                        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
                        {{-- <a href="{{ route('admin.exportIncidentByCompany', ['data' => $request]) }}" style="width: 100%;height: 38px;box-shadow: none;padding-left: 0;padding-right: 0;background: #1a4796;color: #fff;display: flex;justify-content: center;align-items: center;border-radius: 3px;font-size: 15px;margin-left: 10px;border: 1px solid #141d46;">Xuất file</a> --}}
                    </div>
                </form>
            </div>
            <div class="card m-b-30">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="thead-blue">
                                <tr>
                                    <th style="width: 50px">STT</th>
                                    <th>Loại</th>
                                    <th>Thời gian</th>
                                    <th>Download</th>
                                </tr>
                            </thead>
                            @if($items == '')
                                <tr>
                                    <td colspan="11" class="text-center">Không tìm thấy bản ghi nào</td>
                                </tr>
                            @else
                                <?php $count = 0 ?>
                                <tbody>
                                    @foreach($items as $key => $value)
                                    <?php $count++ ?>
                                    <tr>
                                        <td>{{$count}}</td>
                                        <td>{{$value['type']??''}}</td>
                                        <td>{{$value['time']??''}}</td>
                                        <td><a href="{{$value['link']??''}}">Download</a></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
