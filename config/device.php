<?php
return [
    'device_attributes' => [
        // Phòng CNNL
        // Lò hơi công nghiệp tải nhiệt
        '1002796' => [
            'label' => [
                'Nhiên liệu sử dụng (fuel):',
                'Công suất định mức (capacity)(T/h): ',
                'Hiệu suất thiết kế (design efficiency):',
                'Đơn vị nhập khẩu(Imprort agent):',
                'Tiêu chuẩn đánh giá(Assess standard):',
            ],
            'attributes' => [
                'zusefuel.zsym',
                'zpower_capacity',
                'zdesefficien',
                'zimportagent',
                'zassess_standard.zsym',
            ]
        ],
        // Lò hơi công nghiệp
        '1002793' => [
            'label' => [
                'Nhiên liệu sử dụng (fuel):',
                'Công suất định mức (capacity)(T/h):',
                'Hiệu suất thiết kế (design efficiency):',
                'Đơn vị nhập khẩu(Imprort agent):',
                'Tiêu chuẩn đánh giá(Assess standard):',
            ],
            'attributes' => [
                'zusefuel.zsym',
                'zpower_capacity',
                'zdesefficien',
                'zimportagent',
                'zassess_standard.zsym',
            ]
        ],
        // Nhiệt kế chỉ thị số và tương tự
        '1002794' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Dải đo (Scale)',
                'Độ phân giải (Resolution):',
                'Nơi sử dụng (Place):',
            ],
            'attributes' => [
                'zcapchinhxac',
                'zmeasure_scale',
                'zresolution',
                'zplace',
            ]
        ],
        // Thiết bị chỉ thị nhiệt độ
        '1002800' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Dải đo (Scale)',
                'Độ phân giải (Resolution):',
                'Nơi sử dụng (Place):',
            ],
            'attributes' => [
                'zcapchinhxac',
                'zmeasure_scale',
                'zresolution',
                'zplace',
            ]
        ],
        // Rơ le hơi và dòng dầu
        '1002803' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Đường kính (Diameter):',
                'Alarm:',
                'Trip:',
                'Nơi sử dụng (Place):'
            ],
            'attributes' => [
                'zcapchinhxac',
                'zduongkinh',
                'zalarm',
                'zcnnl_trip',
                'zplace',
            ]
        ],
        // Áp kế
        '1002795' => [
            'label' => [
                'Phạm vi đo (Scale):',
                'Độ chính xác (Accuracy):',
                'Cấp chính xác (Class):',
            ],
            'attributes' => [
                'zscope_scale',
                'zaccuracy',
                'zcapchinhxac',
            ]
        ],
        // Nhiệt ẩm kế
        '1002804' => [
            'label' => [
                'Phạm vi đo (Scale):',
                'Độ phân giải (Resolution):',
                'Nhiệt độ (Temperature) ℃:',
                'Độ ẩm (Humidity) %RH :',
            ],
            'attributes' => [
                'zscope_scale',
                'zresolution',
                'ztemperature',
                'zhumidity',
            ]
        ],
        // Rơ le áp suất
        '1002798' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Dải làm việc (Scale):',
                'Nơi sử dụng (Place):',
            ],
            'attributes' => [
                'zcapchinhxac',
                'zwork_scale',
                'zplace',
            ]
        ],
        // Hiệu chỉnh phương tiện đo
        '1002801' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Dải đo (Scale)',
                'Nơi sử dụng (Place):',
            ],
            'attributes' => [
                'zcapchinhxac',
                'zmeasure_scale',
                'zplace',
            ]
        ],
        // Hệ thống lọc bụi
        '1002808' => [
            'label' => [
                'Công suất thiết kế (design Output):',
            ],
            'attributes' => [
                'zpower_design_output',
                'id'
            ]
        ],
        // Tua Bin thủy điện
        '1002807' => [
            'label' => [
                'Công suất định mức (rate ouput) (MW):',
                'Cột áp định mức (Rate Pressure) (mH2O):',
                'Số vòng quay định mức (Rate Speed) (vòng/phút):',
                'Cột áp thí nghiệm (Test Pressure) (mH2O):',
                'Chế dộ thí nghiệm (có tải/không tải/sa thải phụ tải):',
            ],
            'attributes' => [
                'zpower_capacity',
                'zratepress',
                'zrate_speed',
                'zcotap_test_press',
                'zchedothinghiem',
            ]
        ],
        // Hệ thống thông gió
        '1002802' => [
            'label' => [
                'Lưu lượng thiết kế (design flow)(m3/h):',
                'Cột định mức (Rated Pressure)(kPa):',
            ],
            'attributes' => [
                'zdesflow',
                'zpower_capacity',
            ]
        ],
        // Tua Bin hơi
        '1002806' => [
            'label' => [
                'Công suất định mức (rate ouput) (MW):',
                'Tốc độ quay định mức(Rate Speed)(vòng/phút):',
                'Áp suất hơi định mức(Rate Pressure):',
                'Nhiệt độ hơi định mức(Rate Temp):',
                'Áp suất hơi tái nhiệt định mức(Rate Pressure of reheat steam):',
                'Nhiệt độ hơi tái nhiệt định mức(Rate Temp of reheat steam):',
                'Chế dộ thí nghiệm (có tải/không tải/sa thải phụ tải):',
                'Hiệu suất thiết kế (design efficiency): ',
            ],
            'attributes' => [
                'zpower_capacity',
                'zrate_speed',
                'zratepress',
                'zrate_temp',
                'zratepress_reheat',
                'zrate_temp_reheat',
                'zchedothinghiem',
                'zdesefficien',
            ]
        ],
        // Tua Bin khí
        '1002799' => [
            'label' => [
                'Công suất định mức (rate ouput) (MW):',
                'Tốc độ quay định mức(Rate Speed)(vòng/phút):',
                'Áp suất khí cháy tại buồng đốt định mức(Rate Pressure of combustor gas):',
                'Nhiệt độ khí cháy tại buồng đốt định mức(Rate Temp of combustor gas):',
                'Chế dộ thí nghiệm (có tải/không tải/sa thải phụ tải):',
                'Hiệu suất thiết kế (design efficiency): ',
            ],
            'attributes' => [
                'zpower_capacity',
                'zrate_speed',
                'zratepress_combus',
                'zrate_temp_comb',
                'zchedothinghiem',
                'zdesefficien',
            ]
        ],
        // Lò hơi phát điện
        '1002797' => [
            'label' => [
                'Nơi sử dụng (Place):',
                'Hiệu suất thiết kế (design efficiency): ',
                'Công suất định mức (capacity)(T/h): ',
            ],
            'attributes' => [
                'zplace',
                'zdesefficien',
                'zpower_capacity',
            ]
        ],
        // Bộ chuyển đổi áp suất
        '1002805' => [
            'label' => [
                'Cấp chính xác (Class):',
                'Dải đo (Scale)',
                'Nơi sử dụng (Place):',
            ],
            'attributes' => [
                'zcapchinhxac',
                'zmeasure_scale',
                'zplace',
            ]
        ],
        // Phòng rơ le
        // Công tơ
        '1004903' => [
            'label' => [
                'Đơn vị quản lý điểm đo',
                'Đơn vị giao điện năng',
                'Đơn vị nhận điện năng',
                'Vị trí địa lý',
                'Loại điểm đo',
                'Ngày nghiệm thu tĩnh',
                'Ngày nghiệm thu mang tải',
                'Thông tin TU sử dụng',
                'Thông tin TI sử dụng',
                'Hàng kẹp mạch dòng',
                'Hàng kẹp mạch áp',
                'Điện áp(voltage)',
                'Dòng điện(current)',
                'Cấp chính xác P',
                'Cấp chính xác Q',
                'Hằng số xung',
                'Tỷ số TU cài đặt trong công tơ',
                'Tỷ số TI cài đặt trong công tơ',
                'Số lần lập trình',
                'Thời gian lập trình lần cuối',
                'Kết quả kiểm định',
                'Cảnh báo',
                'Hạn kiểm định(Valid until)',
                'Số tem kiểm định',
                'Số seri tem kiểm định',
                'Chức năng',
            ],
            'attributes' => [
                'zdvquanlydiemdosrel.zsym',
                'zdvgiaodiennangsrel.zsym',
                'zdvnhandiennangsrel.zsym',
                'zvitridialy',
                'zloaidiemdosrel.zsym',
                'zngaynttinhdate',
                'zngayntmangtaidate',
                'zTUinform',
                'zTIinform',
                'zhangkepmachdong',
                'zhangkepmachap',
                'zdienapsrel.zsym',
                'zdongdiensrel.zsym',
                'zcapcxpsrel.zsym',
                'zcapcxqsrel.zsym',
                'zhangsosxungsrel.zsym',
                'ztysotu',
                'ztysoti',
                'zsolanlaptrinh',
                'zlasttimelaptrinhdate',
                'zkqkiemdinhsrel.zsym',
                'zcanhbao',
                'zhankiemdinhdate',
                'zsotemkiemdinh',
                'zseritemkiemding',
                'zchucnangsrel.zsym',
            ]
        ],
        // Máy biến dòng đo lường
        '1004904' => [
            'label' => [
                'Hạn kiểm đinh(valid until)',
                'Số tem kiểm định',
                'Số seri tem kiểm định',
                'Kết quả kiểm định',
                'Tỷ số biến dòng',
                'Cấp chính xác',
                'Dung lượng',
                'Niêm phong nắp boóc',
                'Chức năng',
            ],
            'attributes' => [
                'zhankiemdinhdate',
                'ztemkiemdinhnum',
                'zseritemkiemding',
                'zkqkiemdinhsrel.zsym',
                'ztysobiendongstr',
                'zcapchinhxacstr',
                'zdungluonstr',
                'zniemphongnapbocstr',
                'zchucnangsrel.zsym',
            ]
        ],
        // Máy biến điện áp đo lường
        '1004905' => [
            'label' => [
                'Hạn kiểm đinh(valid until)',
                'Số tem kiểm định',
                'Số seri tem kiểm định',
                'Kết quả kiểm định',
                'Tỷ số biến dòng',
                'Cấp chính xác',
                'Dung lượng',
                'Chức năng',
            ],
            'attributes' => [
                'zhankiemdinhdate',
                'ztemkiemdinhnum',
                'zseritemkiemding',
                'zkqkiemdinhsrel.zsym',
                'ztysobiendongstr',
                'zcapchinhxacstr',
                'zdungluonstr',
                'zchucnangsrel.zsym',
            ]
        ],
        //Thiết bị thí nghiệm và kiểm định
        '1004906' => [
            'label' => [
                'Hạn kiểm đinh/hiệu chuẩn(valid until)',
                'Số tem kiểm định/hiệu chuẩn',
                'Số seri tem kiểm định/hiệu chuẩn',
                'Đặc trưng kỹ thuật',
            ],
            'attributes' => [
                'zhankiemdinhdate',
                'ztemkiemdinhnum',
                'zseritemkiemding',
                'zdtkythuatstr',
            ]
        ],
        // Phòng PXCĐ
        // Cáp và dây dẫn(Cáp)
        '1002851' => [
            'label' => [
                'Đơn vị sử dụng(User):',
                'Số hợp đồng (Contract number)',
                'Tên cáp(Cable name)',
                'Tên mẫu thử(Sample):',
                'Cấp điện áp danh định',
                'Tiết diện mặt cắt 1:',
                'Tiết diện mặt cắt 2:',
                'Hình dạng ruột dẫn',
                'Số ruột dẫn',
                'Loại vật liệu bọc cách điện',
                'Loại vật liệu lớp vỏ bọc ngoài',
                'Vật liệu lõi dây dẫn',
                'Giáp bảo vệ',
            ],
            'attributes' => [
                'zdonvisudung.combo_name',
                'zcontract_number',
                'zcable_name',
                'zsample',
                'zcapdienap_dd.zsym',
                'ztietdienmatcat.zsym',
                'ztietdienmatcat2.zsym',
                'zhinhdangruotdan.zsym',
                'zsoruotdan',
                'zloaivatlieu_bcd.zsym',
                'zloaivatlieu_voboc.zsym',
                'zvatlieu.zsym',
                'zgiapbaove.zsym',
            ],
            'info_device' => [
                'label' => [
                    'Tình trạng (Status):'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Máy cắt hạ thế
        '1002853' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (V):',
                'Dòng điện định mức (A)',
                'Cấp bảo vệ',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
                'zcapbaove',
            ],
            'info_device' => [
                'label' => [
                    'Tình trạng (Status):'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Dụng cụ an toàn
        '1002852' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (kV):',
                'Điện áp sử dụng (kV)',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Dụng cụ an toàn - bút thử điện
        '1002850' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (kV):',
                'Điện áp sử dụng (kV)',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Dụng cụ an toàn - Dây đeo an toàn
        '1002855' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (kV):',
                'Điện áp sử dụng (kV)',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Contactor
        '1002854' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (V):',
                'Điện áp cuộn dây (V)',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdienapcuonday',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Rơ le nhiệt
        '1002849' => [
            'label' => [
                'Niêm phong:',
                'Điện áp định mức (V):',
                'Dòng điện định mức (A)',
            ],
            'attributes' => [
                'zniemphong',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Phòng TDH
        // Gateway
        '1002847' => [
            'label' => [
                'Hệ điều hành',
                'Phiên bản',
                'IP',
                'Thời gian lắp đặt',
            ],
            'attributes' => [
                'zhedieuhanh.zsym',
                'zversion',
                'zIP',
                'zTGLD',
            ]
        ],
        // HMI
        '1002846' => [
            'label' => [
                'Hệ điều hành',
                'Phiên bản',
                'IP',
                'Thời gian lắp đặt',
            ],
            'attributes' => [
                'zhedieuhanh.zsym',
                'zversion',
                'zIP',
                'zTGLD',
            ]
        ],
        // RTU
        '1002844' => [
            'label' => [
                'Hệ điều hành',
                'Phiên bản',
                'IP',
                'Thời gian lắp đặt',
            ],
            'attributes' => [
                'zhedieuhanh.zsym',
                'zversion',
                'zIP',
                'zTGLD',
            ]
        ],
        // Multimeter
        '1002818' => [
            'label' => [
                'Đặc trưng kỹ thuật (Technical specifications):',
                'Cấp chính xác(Class):',
                'Tỉ số TU (TU Ratio):',
                'Tỉ số TI (TI Ratio):',
            ],
            'attributes' => [
                'ztech_spec',
                'zcapchinhxac',
                'ztu_ratio',
                'zti_ratio',
            ]
        ],
        // Transducer
        '1003417' => [
            'label' => [
                'Cấp chính xác(Class):',
                'Dải đo đầu vào (Input scale):',
                'Dải đo đầu ra (Output scale):',
                'Tin hiệu đo (Singal)',
                'IP',
                'Thời gian lắp đặt',
            ],
            'attributes' => [
                'zcapchinhxac',
                'ztdh_input',
                'ztdh_output',
                'ztdh_signal',
                'zIP',
                'zTGLD',
            ]
        ],
        // Phòng cao áp
        // Máy biến áp (Máy biến áp lực)
        '1002785' => [
            'label' => [
                'Công suất (kVA) (Capacity)',
                'Tổ đấu dây (Vector group)',
                'Điện áp định mức (kV) (Rate voltage)',
                'Thông tin bộ OLTC',
                'Kiểu OLTC (Type of OLTC)',
                'Kiểu bộ truyền động OLTC (Type of Motor drive nit)',
            ],
            'attributes' => [
                'zcapacity',
                'ztodauday.zsym',
                'zdienapdinhmuc',
                'zthongtin_oltc',
                'ztypeotlc.zsym',
                'ztypeotlc_motor.zsym'
            ],
            'info_device' => [
                'label' => [
                    'Tình trạng (State)',
                    'Số chế tạo (Serial Number):',
                    'Hãng sản xuất (Manufacturer)',
                    'Năm sản xuất (Year of Manufacture)',
                ],
                'attributes' => [
                    'zStage.zsym',
                    'serial_number',
                    'zManufacturer.zsym',
                    'zYear_of_Manafacture.zsym'
                ]
            ]
        ],
        // Máy cắt
        '1002782' => [
            'label' => [
                'Cách điện (trường này ẩn trong biên bản)',
                'Điện áp định mức (Rated voltage)(kV):',
                'Dòng điện định mức (Rated current)(A):',
                'Dòng cắt định mức (Rated short curcuit current) (kA):',
            ],
            'attributes' => [
                'zcachdien.zsym',
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
                'zrate_short_cc',
            ]
        ],
        // Dao cách ly
        '1002771' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Dòng điện định mức (A) (Rate current)',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ]
        ],
        // Máy biến dòng
        '1002783' => [
            'label' => [
                'Dung lượng danh định (Rate Burden) (VA):',
                // 'Cấp chính xác (Accuracy class of):',
                'Tỉ số biến (Ratio) (V/V)',
                // 'Mức cách điện (Insulation level) (kV)',
                // 'Vị trí lắp đặt (Installed Position)',
            ],
            'attributes' => [
                'zdungluongtai',
                'zratio',
            ],
            'info_device' => [
                'label' => ['
                    Tình trạng'
                ],
                'attributes' => [
                    'zStage.zsym'
                ]
            ]
        ],
        // Máy biến điện áp
        '1002779' => [
            'label' => [
                'Dung lượng danh định (Rate Burden) (VA)',
                // 'Cấp chính xác (Accuracy class of)',
                'Tỉ số biến (Ratio) (V/V)',
                // 'Mức cách điện (Insulation level) (kV)',
                // 'Vị trí lắp đặt (Installed Position)',
            ],
            'attributes' => [
                'zdungluongtai',
                'zratio',
            ]
        ],
        // Chống sét van
        '1002788' => [
            'label' => [
                'Điện áp định mức (Rated voltage) (kV)',
                'Điện áp vận hành liên tục UCOV (kV) (Continuous operating voltage) ',
                'Dòng phóng danh định (Nominal discharge current) (kA)',
                // 'Vị trí lắp đặt (Installed Position)'
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdienapvanhanhlt',
                'zdongphongdanhdinh',
            ]
        ],
        // Cáp lực
        '1002773' => [
            'label' => [
                'Tiết diện cáp (Conductor cross-section): (mm2)',
                'Điện áp định mức (kV) (Rate voltage)',
            ],
            'attributes' => [
                'ztietdiencap.zsym',
                'zdienapdinhmuc',
            ]
        ],
        // Máy điện quay
        '1002781' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Dòng điện định mức (Rated curent) (Α):',
                'Tốc độ quay (Velocity of rotor) (r/min): ',
                // 'Hệ số công suất Cosϕ: ',
                'Điện áp kích thích (Excited voltage) (V): ',
                'Dòng điện kích thích (Excited current) (A): ',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
                'zvelocity_rotor',
                // 'zhesocosj',
                'zdienapkichthich',
                'zdongdienkichthich',
            ]
        ],
        // Sứ cách điện
        '1002774' => [
            'label' => [
                'Điện áp định mức (Rated voltage) (kV):',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'id'
            ]
        ],
        // Mẫu cách điện
        '1002772' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Số lượng mẫu thử (Number of samples):',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'znum_samp',
            ],
            'info_report' => [
                'zCA7' => [
                    'label' => [
                        'Số hợp đồng mua sắm',
                        'Phiếu giao nhiệm vụ ',
                    ],
                    'attributes' => [
                        'z1hopdongmuasamstr',
                        'z2phieugiaonhiemvustr',
                    ],
                ]
            ]
        ],
        // Tụ điện
        '1002770' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Công suất (Capacity) (kVA)',
                'Điện dung định mức (Rated capacitance) (μF): ',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zcapacity',
                'zdienapcuonday',
            ]
        ],
        // Kháng điện
        '1002786' => [
            'label' => [
                'Điện áp định mức (Rated voltage) (kV):',
                'Dòng điện định mức (Rated current) (A):',
                'Công suất (Capacity)(VA):',
                'Ký hiệu đấu dây (Conection symbol):',
                'Điện kháng thứ tự không (Impedance) (Ω):',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
                'zcapacity',
                'zconnection_symbol',
                'zdienkhang_impedance',
            ]
        ],
        // Động cơ điện
        '1002787' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Dòng điện định mức (Rated curent) (Α):',
                'Công suất định mức (Rate Capacity) (kW)',
                'Hệ số công suất Cosϕ: ',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
                'zcapacity'
            ]
        ],
        // Máy biến áp phân phối
        '1002784' => [
            'label' => [
                'Công suất (kVA) (Capacity)',
                'Tổ đấu dây (Vector group)',
                'Điện áp định mức (kV) (Rate voltage)'
            ],
            'attributes' => [
                'zcapacity',
                'ztodauday.zsym',
                'zdienapdinhmuc'
            ],
            'info_report' => [
                'zCA13_8' => [
                    'label' => [
                        'Số hợp đồng mua sắm',
                        'Phiếu giao nhiệm vụ ',
                    ],
                    'attributes' => [
                        'zsohopdongstr',
                        'zphieugiaoviec',
                    ],
                ],
                'zCA13_9' => [
                    'label' => [
                        'Số hợp đồng mua sắm',
                        'Phiếu giao nhiệm vụ ',
                    ],
                    'attributes' => [
                        'zsohopdongstr',
                        'zphieugiaoviec',
                    ],
                ]
            ]
        ],
        // Xe gầu cách điện
        '1005622' => [
            'label' => [
                'Điện áp định mức (kV) (Rate line voltage)',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'id',
            ]
        ],
        // Bộ đếm sét
        '1002775' => [
            'label' => [
            ],
            'attributes' => [
            ]
        ],
        // Dao cắt tải
        '1005404' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
                'Dòng điện định mức (A) (Rate current)',
                // 'Buồng dập hồ quang (Arc chamber type)',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'zdongdiendinhmuc',
            ]
        ],
        // Hệ thống GIS(GIS)
        '1003465' => [
            'label' => [
                'Điện áp định mức (kV) (Rate voltage)',
            ],
            'attributes' => [
                'zdienapdinhmuc',
                'id',
            ]
        ],
        // Phòng rơ le
        // Rơ le bảo vệ - F87T
        '1005464' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ],
        ],
        // Rơ le bảo vệ - F87B - M
        '1005465' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F87B - S
        '1005467' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F87B - U
        '1005466' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F87L
        '1005469' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F21
        '1005470' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F67
        '1005471' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F50/51
        '1005472' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F25/79/50BF
        '1005481' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F25/79
        '1005473' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - BCU
        '1003416' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F50BF
        '1005480' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F67NS
        '1005486' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F81/BCU
        '1005478' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F50/F81/BCU
        '1005479' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F50/F81
        '1005476' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F90
        '1002823' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F32
        '1005482' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F32
        '1005483' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - FL
        '1005485' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - FR
        '1005484' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F87G
        '1005463' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F87R
        '1005468' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
        // Rơ le bảo vệ - F27/59
        '1005474' => [
            'label' => [
                'IP',
                'Phiếu chỉnh định',
            ],
            'attributes' => [
                'zIP',
                'zrole_ip',
            ]
        ],
    ]
];
