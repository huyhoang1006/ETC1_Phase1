@extends('machines::master')
@section('content2')
<div class="card m-b-30">
    <div class="card-body module_search v-right" style=" padding-top: 20px;padding-bottom: 20px;">
        <div class="form-group form_input mb-0">
            <select class="select2-single form-control" name="state" id="ViewReport">
                <option value="">Chọn loại báo cáo</option>
                <option value="{{ config('constant.high_pressure_report.report_overview_check') }}"  {{ session()->get('numberReport') == config('constant.high_pressure_report.report_overview_check') ? 'selected' : '' }}>1. Báo cáo kiểm tra bên ngoài</option>
                <option value="{{ config('constant.high_pressure_report.report_insulation_resistance') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_insulation_resistance') ? 'selected' : '' }}>2. Báo cáo điện trở cách điện</option>
                <option value="{{ config('constant.high_pressure_report.report_contact_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time') ? 'selected' : '' }}>3. Báo cáo điện trở tiếp xúc</option>
                <option value="{{ config('constant.high_pressure_report.report_contact_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time') && session()->get('bdg') == true ? 'selected' : '' }} data-bdg="true">3.1. Báo cáo điện trở tiếp xúc - Bảng đánh giá</option>
                <option value="{{ config('constant.high_pressure_report.report_cutting_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_cutting_time') ? 'selected' : '' }}>4. Báo cáo thời gian cắt</option>
                <option value="{{ config('constant.high_pressure_report.report_cutting_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_cutting_time') && session()->get('bdg') == true ? 'selected' : '' }} data-bdg="true">4.1. Báo cáo thời gian cắt - Bảng đánh giá</option>
                <option value="{{ config('constant.high_pressure_report.report_close_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_close_time') ? 'selected' : '' }}>5. Báo cáo thời gian đóng</option>
                <option value="{{ config('constant.high_pressure_report.report_close_time') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_close_time') && session()->get('bdg') == true ? 'selected' : '' }} data-bdg="true">5.1. Báo cáo thời gian đóng - Bảng đánh giá</option>
                <option value="{{ config('constant.high_pressure_report.report_contact_time_co') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time_co') ? 'selected' : '' }}>6. Báo cáo thời gian tiếp xúc ở chế độ CO</option>
                <option value="{{ config('constant.high_pressure_report.report_contact_time_co') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time_co') && session()->get('bdg') == true ? 'selected' : '' }} data-bdg="true">6.1. Báo cáo thời gian tiếp xúc ở chế độ CO - Bảng đánh giá</option>
                <option value="{{ config('constant.high_pressure_report.report_stop_contact_time_oco') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time_co') ? 'selected' : '' }}>7. Báo cáo thời gian ngừng tiếp xúc ở chế độ O-CO</option>
                <option value="{{ config('constant.high_pressure_report.report_stop_contact_time_oco') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_contact_time_co') && session()->get('bdg') == true ? 'selected' : '' }} data-bdg="true">7.1. Báo cáo thời gian ngừng tiếp xúc ở chế độ O-CO - Bảng đánh giá</option>
                <option value="{{ config('constant.machines_report.intake_air_pressure') }}" {{ session()->get('numberReport') == config('constant.machines_report.intake_air_pressure') ? 'selected' : '' }}>8. Báo cáo áp lực khí nạp ở t=20°C</option>
                <option value="{{ config('constant.machines_report.report_insulation_resistance') }}" {{ session()->get('numberReport') == config('constant.machines_report.report_insulation_resistance') ? 'selected' : '' }}>9. Báo cáo cuộn đóng/ cuộn cắt 1/ cuộn cắt 2(gồm điện trở 1 chiều và điện trở cách điện)</option>
                <option value="{{ config('constant.machines_report.report_accumulative_engine') }}" {{ session()->get('numberReport') == config('constant.machines_report.report_accumulative_engine') ? 'selected' : '' }}>10. Báo cáo động cơ tích năng(gồm điện trở 1 chiều và điện trở cách điện)</option>
                <option value="{{ config('constant.machines_report.report_check_transmission_mechanism') }}" {{ session()->get('numberReport') == config('constant.machines_report.report_check_transmission_mechanism') ? 'selected' : '' }}>11. Báo cáo kiểm tra cơ cấu truyền động</option>
                <option value="{{ config('constant.machines_report.report_ac_voltage_rises_high') }}" {{ session()->get('numberReport') == config('constant.machines_report.report_ac_voltage_rises_high') ? 'selected' : '' }}>12. Báo cáo điện áp xoay chiều tăng cao(*)</option>
                <option value="{{ config('constant.high_pressure_report.report_first_trip') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_first_trip') ? 'selected' : '' }}>13. Báo cáo đo đặc tính first-trip</option>
                <option value="{{ config('constant.high_pressure_report.report_wide_resistance') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_wide_resistance') ? 'selected' : '' }}>14. Báo cáo đo đặc tính điện trở động</option>
                <option value="{{ config('constant.high_pressure_report.report_cruise_characteristics') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_cruise_characteristics') ? 'selected' : '' }}>15. Báo cáo đo đặc tính hành trình</option>
                <option value="{{ config('constant.high_pressure_report.report_partial_discharge') }}" {{ session()->get('numberReport') == config('constant.high_pressure_report.report_partial_discharge') ? 'selected' : '' }}>16. Báo cáo đo phóng điện cục bộ</option>
            </select>
        </div>
        <div class="form-group form_submit mb-0">
            <button id="btnViewReport" type="button" class="btn btn-dark" onclick="gotoReport()">Xem báo cáo</button>
        </div>
        <input type="hidden" id="errors" value="{{ json_encode($errors->all()) }}">
    </div>
</div>
@endsection
@push('scripts')
<script>
    $(function(){
        let errors = JSON.parse($('#errors').val());
        console.log(errors);
        if(errors.length > 0){
            for (let i = 0; i < errors.length; i++) {
                toastr["error"](errors[i]);
            }
        }
    });
    function gotoReport() {
        let classType = '{{ getTypeOfCuttingMachine(request()->deviceType) }}';
        let viewContent = document.getElementById('ViewReport').value;
        console.log('viewContent: ', viewContent);
        let ids = [];
        let viewchecks = $('#viewCheck[type=checkbox]:checked').each(function(e){
            ids.push( $(this).val() );
        });

        let totalChecked = $('input[type=checkbox]:checked').length;
        // check select report
        if( !viewContent ){
            toastr["error"]('Vui lòng chọn loại báo cáo!');
            return;
        }
        // check selected report
        if( ids.length == 0 ){
            toastr["error"]('Vui lòng chọn biên bản để xuất báo cáo!');
            return;
        }
        const request = $('#form').serialize();
        const title = $('#ViewReport').find(':selected').data('title');
        const bdg = $('#ViewReport').find(':selected').data('bdg') ?? '';
        if(viewContent == '{{ config('constant.high_pressure_report.report_overview_check') }}'){
            window.location.href = '{{route('admin.highPressure.externalInspectionReport')}}?ids=' +  ids.join(',')+'&classType='+classType+'&'+request;
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_insulation_resistance') }}'){
            const url = '{{route('admin.highPressure.insulationResistanceReport')}}?ids=' +  ids.join(',')+'&classType='+classType+'&'+request;
            ajaxValidate(ids, classType, url);
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_cutting_time') }}'){
            const url = '{{route('admin.highPressure.cuttingTimeReport')}}?ids=' +  ids.join(',')+`&classType=${classType}&${request}&bdg=${bdg ?? ''}`;
            ajaxValidate(ids, classType, url);
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_contact_time') }}'){
            const url = '{{route('admin.highPressure.contactTimeReport')}}?ids=' +  ids.join(',')+`&classType=${classType}&${request}&bdg=${bdg ?? ''}`;
            ajaxValidate(ids, classType, url);
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_close_time') }}'){
            const url = '{{route('admin.shareReportPreview')}}?ids=' +  ids.join(',')+`&classType=${classType}&title=thoi-gian-dong&${request}&bdg=${bdg}`;
            ajaxValidate(ids, classType, url);
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_contact_time_co') }}'){
            const url = '{{route('admin.shareReportPreview')}}?ids=' +  ids.join(',')+`&classType=${classType}&title=thoi-gian-tiep-xuc-o-che-do-co&${request}&bdg=${bdg}`;
            ajaxValidate(ids, classType, url);
        }
        if(viewContent == '{{ config('constant.high_pressure_report.report_stop_contact_time_oco') }}'){
            const url = '{{route('admin.shareReportPreview')}}?ids=' +  ids.join(',')+`&classType=${classType}&title=thoi-gian-ngung-tiep-xuc-o-che-do-o-co&${request}&bdg=${bdg}`;
            ajaxValidate(ids, classType, url);
        }
        if (viewContent == '{{ config('constant.machines_report.intake_air_pressure') }}') {
            const url = '{{route('admin.intakeAirPressureReport')}}?ids=' +  ids.join(',') + '&classType='+classType+'&'+request;
            ajaxValidate(ids, classType, url);
        }
        if (viewContent == '{{ config('constant.machines_report.report_insulation_resistance') }}') {
            const url = '{{route('admin.insulationIndex')}}?ids=' +  ids.join(',') + '&classType='+classType+'&'+request;
            ajaxValidate(ids, classType, url);
        }
        if (viewContent == '{{ config('constant.machines_report.report_accumulative_engine') }}') {
            const url = '{{route('admin.accumulativeEngineReport')}}?ids=' +  ids.join(',') + '&classType='+classType+'&'+request;
            ajaxValidate(ids, classType, url);
        }
        if (viewContent == '{{ config('constant.machines_report.report_check_transmission_mechanism') }}') {
            const url = '{{route('admin.checkTransmissionMechanism')}}?ids=' +  ids.join(',') + '&classType='+classType+'&'+request;
            window.location.href = url;
        }
        if (viewContent == '{{ config('constant.machines_report.report_ac_voltage_rises_high') }}') {
            const url = '{{route('admin.voltageRisesHighReport')}}?ids=' +  ids.join(',') + '&classType='+classType+'&'+request;
            ajaxValidate(ids, classType, url);
        }
        if (viewContent == '{{ config('constant.high_pressure_report.report_first_trip') }}') {
            window.location.href = '{{route('admin.shareReportTable')}}?ids=' +  ids.join(',') + `&classType=${classType}&title=dac-tinh-first-trip`+'&'+request;
        }
        if (viewContent == '{{ config('constant.high_pressure_report.report_wide_resistance') }}') {
            window.location.href = '{{route('admin.shareReportTable')}}?ids=' +  ids.join(',') + `&classType=${classType}&title=dac-tinh-dien-tro-rong`+'&'+request;
        }
        if (viewContent == '{{ config('constant.high_pressure_report.report_cruise_characteristics') }}') {
            window.location.href = '{{route('admin.shareReportTable')}}?ids=' +  ids.join(',') + `&classType=${classType}&title=dac-tinh-hanh-trinh`+'&'+request;
        }
        if (viewContent == '{{ config('constant.high_pressure_report.report_partial_discharge') }}') {
            window.location.href = '{{route('admin.shareReportTable')}}?ids=' +  ids.join(',') + `&classType=${classType}&title=phong-dien-cuc-bo`+'&'+request;
        }
    }
    // call ajax to validate when submit preview report
    function ajaxValidate(ids, classType, urlRedirect){
        $('.overlay').removeClass('hidden');
        $.ajax({
            url: "{{ route('admin.ajaxValidate') }}",
            type: 'POST',
            dataType: 'JSON',
            data: {
                _token: "{{ csrf_token() }}",
                ids,
                classType,
            },
            success: function(result){
                $('.overlay').addClass('hidden');
                if (result['success'] == false) {
                    for (let i = 0; i < result['errors'].length; i++) {
                        toastr["error"](result['errors'][i]);
                    }
                }
                if (result['success'] == true) {
                    window.location.href = urlRedirect;
                }
            }
        });
    }
</script>
@endpush
