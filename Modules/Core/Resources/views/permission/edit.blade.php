@extends('layouts.master')

@section('pageTitle', 'Quản lý phân quyền')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page"><h3>Quản lý phân quyền > Cập nhật quyền > UserId: {{ $userid }}</h3></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('content')
   <div class="row">
        <div class="col-md-12 table-responsive">
            <form action="{{ route('admin.postEditPermission') }}" method="POST">
                @csrf
                <input type="hidden" name="userid" value="{{ $userid }}">
                <div class="card-body" style=" padding-top: 15px;padding-bottom: 20px;">
                    <div class="form-group form_input" style="width: 30%;">
                        <p>Kiểu truy cập</p>
                        <select class="select2-single form-control" id="access_type" name="access_type">
                            @foreach ($accessTypes as $item)
                                <option value="{!! $item['id'].'*'.$item['sym'] !!}" {{ @$userInfo['accessType']['id'] == $item['id'] ? 'selected' : '' }} >{!! $item['sym'] !!}</option>
                            @endforeach
                        </select>
                        @if ($errors->first('access_type'))
                            <span style="color:red">{{ $errors->first('access_type') }}</span>
                        @endif
                    </div>
                    <div class="form-group form_submit mb-0">
                        <button type="submit" class="btn btn-primary" id="btn-submit">Cập nhật</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

