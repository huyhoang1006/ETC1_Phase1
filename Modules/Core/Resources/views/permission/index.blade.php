@extends('layouts.master')

@section('pageTitle', 'Quản lý phân quyền')

@section('breadcumb')
    <div class="breadcrumbbar">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="breadcrumb-list">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page"><h3>Quản lý phân quyền</h3></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('content')
   <div class="row">
        <div class="col-md-12">
            @if (session()->has('update_success'))
            <div class="alert alert-success alert-dismissible fade show alert-permission" role="alert">
                {{ session()->get('update_success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
        </div>
        <div class="col-md-12">
            @if (session()->has('error'))
                <div class="alert alert-dangers">
                    {{ session()->get('error') }}
                </div>
            @endif
        </div>
        <div class="col-md-12 table-responsive">
            <table class="table table-striped">
                <thead class="thead-blue">
                    <th>UserId</th>
                    <th>Domain</th>
                    <th>Kiểu truy cập</th>
                    <th>Chỉnh sửa</th>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr>
                        <td>{{ $user['userid'] }}</td>
                        <td>{{ @$user['domain_name'] }}</td>
                        <td>{{ @$user['access_name'] }}</td>
                        <td>
                            <a class="btn btn-primary btn-view-user" href="{{ route('admin.permission.edit', $user['userid']) }}">Chỉnh sửa</a>
                        </td>
                    </tr>                        
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

