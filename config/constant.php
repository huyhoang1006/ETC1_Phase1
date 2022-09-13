<?php

return [
    'measure' => [
        'device' => [
            'type' => [
                'meter' => 'Công tơ',
                'measuring_current_transformer' => 'Biến dòng đo lường',
                'measurement_transformer' => 'Biến áp đo lường',
            ]
        ]
    ],
    'electromechanical' => [
        'device' => [
            'type' => [
                'cap_boc_ha_ap' => 'Cáp bọc hạ áp',
                'cap_boc_trung_ap' => 'Cáp bọc trung áp',
                'cap_luc_trung_the' => 'Cáp lực trung thế',
                'cap_van_xoan' => 'Cáp vặn xoắn',
                'day_dan_tran' => 'Dây dẫn trần',
            ]
        ]
    ],
    'report' => [
        'device' => [
            'may_cat' => 'Máy cắt',
            'oltc' => 'OLTC',
            'may_bien_ap' => 'Máy biến áp',
        ]
    ],
    'device_mc' => [
        '3_bo' => 400063,
        '1_bo_1_buong' => 400062,
        '1_bo_2_buong' => 401101,
    ],
    'high_pressure_report' => [
        'report_overview_check' => 1,
        'report_insulation_resistance' => 3,
        'report_cutting_time' => 5,
        'report_contact_time' => 4,
        'report_close_time' => 6,
        'report_contact_time_co' => 7,
        'report_stop_contact_time_oco' => 8,
        'report_first_trip' => 14,
        'report_wide_resistance' => 15,
        'report_cruise_characteristics' => 16,
        'report_partial_discharge' => 17,
    ],
    'machines_report' => [
        'intake_air_pressure' => 9,
        'report_insulation_resistance' => 10,
        'report_accumulative_engine' => 11,
        'report_check_transmission_mechanism' => 12,
        'report_ac_voltage_rises_high' => 13,
    ],
    'name_sheet' => [
        'sheetOne' => 'DATA_1',
        'sheetTwo' => 'DATA_2',
        'sheetThree' => 'DATA_3',
        'sheetFor' => 'DATA_4',
        'sheetFive' => 'DATA_5',
        'sheetSix' => 'DATA_6',
        'sheetSeven' => 'DATA_7',
        'sheetEight' => 'DATA_8',
        'sheetNine' => 'DATA_9',
        'sheetTen' => 'DATA_10',
        'sheetAll' => 'THONG_KE_DU_LIEU',
        'sheetChart' => 'BIEU_DO',
    ],
    'high_pressure_transformers_report' => [
        'report_overview_check' => 1,
        'report_leakage_impedance' => 12,
        'report_sweep_frequency' => 13,
        'report_solid_insulation' => 14,
        'report_local_electricity_online' => 15,
        'report_local_electricity_offline' => 16,
        'report_pressure_under_load' => 17,
        'report_ac_voltage' => 18,
        'report_inductive_ac_voltage' => 19,
        'report_no_load_loss' => 20,
        'report_short_circuit_voltage' => 21,
        'report_terminal_element' => 7,
        'report_rate_of_change' => 10,
        'report_porcelain_test' => 8,
    ],
    'machines_report_transformers_report' => [
        'currentAndNoLoadLossReport' => 2,
        'oneWayResistorReport' => 9,
        'syllableWordCircuit' => 3,
    ],
    'device_statistical' => [
        '1002785' => 'Máy biến áp',
        '1002784' => 'Máy biến áp phân phối',
        '1002783' => 'Máy biến dòng',
        '1002779' => 'Máy biến điện áp',
        '1002782' => 'Máy cắt',
        '1005621' => 'OLTC',
        '1002788' => 'Chống sét van',
        '1002771' => 'Dao cách ly',
        '1002773' => 'Cáp lực',
        '1002786' => 'Kháng điện',
        '1002770' => 'Tụ điện',
        '1002776' => 'Mẫu cáp(xung sét cáp)',
        '1002772' => 'Mẫu cách điện',
    ],
    'permissions' => [
        'high_pressure' => 'CA',
        'energy' => 'CNNL',
        'measure' => 'DL',
        'petrochemical' => 'HD',
        'electromechanical' => 'PXCD',
        'relay' => 'RL',
        'automation' => 'TDH',
    ]
];
