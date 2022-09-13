<template>
    <div>
        <div class="row align-items-center m-b-20">
            <div class="col-md-8 col-lg-8">
                <h4 class="page-title">Danh sách thiết bị thí nghiệm hiệu chỉnh: 110kV Bãi Sậy</h4>
            </div>
            <div class="col-md-4 col-lg-4">
                <div class="widgetbar text-right">
                    <button v-on:click="changeTableView" class="btn btn-primary-rgba show_list list_tree"><i class="fa fa-list mr-2"></i>Dạng list</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">

                <div class=" m-b-30">
                    <div v-if="display === 'list'">
                        <div class="card m-b-30">
                            <div class="card-header" style="padding: 20px; padding-bottom: 0px;">
                                <h5 class="card-title"><i class="feather icon-search mr-2"></i> Bộ lọc</h5>
                            </div>
                            <div class="card-body module_search" style=" padding-top: 15px;">
                                <div class="form-group form_input mb-0">
                                    <input type="text" class="form-control" name="inputText" id="inputText" placeholder="Tìm kiếm theo tên, Serial, Kiểu, HSX......">
                                </div>
                                <div class="form-group form_select mb-0">
                                    <select class="select2-single form-control" name="state">
                                        <option>Ngăn lộ</option>
                                        <option value="hn">MBA TD32</option>
                                        <option value="hy">Ngăn 373</option>
                                        <option value="hy">TU C31</option>
                                        <option value="hy">Ngăn MBA T1</option>
                                        <option value="hy">Hệ thống SCADA</option>
                                        <option value="hy">Thanh cái C31</option>
                                        <option value="hy">Ngăn TU C31</option>
                                        <option value="hy">Ngăn MBA T1</option>
                                        <option value="hy">Tủ AC,DC</option>
                                    </select>
                                </div>
                                <div class="form-group form_select mb-0">
                                    <select class="select2-single form-control" name="state">
                                        <option>Năm sản xuất</option>
                                        <option value="hn">2021</option>
                                        <option value="hy">2020</option>
                                        <option value="hy">2019</option>
                                        <option value="hy">2018</option>
                                        <option value="hy">2017</option>
                                        <option value="hy">2016</option>
                                        <option value="hy">2015</option>
                                        <option value="hy">2014</option>
                                        <option value="hy">2013</option>
                                        <option value="hy">2012</option>
                                        <option value="hy">2011</option>
                                        <option value="hy">2010</option>
                                        <option value="hy">2009</option>
                                        <option value="hy">2008</option>
                                        <option value="hy">2007</option>
                                        <option value="hy">2006</option>
                                        <option value="hy">2005</option>
                                        <option value="hy">2004</option>
                                        <option value="hy">2003</option>
                                        <option value="hy">2002</option>
                                        <option value="hy">2001</option>
                                        <option value="hy">2000</option>
                                        <option value="hy">1999</option>
                                        <option value="hy">1998</option>
                                        <option value="hy">1997</option>
                                        <option value="hy">1996</option>
                                        <option value="hy">1995</option>
                                        <option value="hy">....</option>
                                    </select>
                                </div>
                                <div class="form-group form_submit mb-0">
                                    <button type="button" class="btn btn-dark">Tìm kiếm</button>
                                </div>
                            </div>
                        </div>
                        <div  class="card">
                            <div class="card-body min_table list_list">

                            <div class="table_minwidth">
                                <table class="table table-striped">
                                    <thead class="thead-dark">
                                    <tr>
                                        <th>Nhóm</th>
                                        <th>Khu vực</th>
                                        <th>Trạm</th>
                                        <th>Ngăn lộ</th>
                                        <th>Tên thiết bị</th>
                                        <th>Serial</th>
                                        <th>Kiểu</th>
                                        <th>Dòng điện</th>
                                        <th>Cấp chính xác</th>
                                        <th>Hãng sản xuất</th>
                                        <th>Năm sản xuất</th>
                                        <th>Vị trí lắp đặt</th>
                                        <th>Khu vực</th>
                                        <th>Trạm</th>
                                        <th>Ngăn lộ</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(item, index) of allDevices">
                                        <td>{{  item.groupName }}</td>
                                        <td>{{  item.cityName }}</td>
                                        <td>{{  item.stationName }}</td>
                                        <td>{{  item.blockingRoadName }}</td>
                                        <td>{{  item.equipmentTypeName }}</td>
                                        <td>{{  item.serial }}</td>
                                        <td>{{  item.model }}</td>
                                        <td>{{  item.electricName }}</td>
                                        <td>{{  item.exactLevelId }}</td>
                                        <td>{{  item.manufacturerName }}</td>
                                        <td>{{  item.yearOfManufacture }}</td>
                                        <td>{{  item.blockingRoadName }}</td>
                                        <td>{{  item.cityName }}</td>
                                        <td>{{  item.stationName }}</td>
                                        <td>{{  item.blockingRoadName }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                        <div class="total_pana">
                            <div class="total">
                                <p>Showing 1 to 10 of 57 entries</p>
                            </div>
                            <div class="napa">
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item">
                                            <a class="page-link" href="#" aria-label="Previous">
                                                <span aria-hidden="true">«</span>
                                                <span class="sr-only">Previous</span>
                                            </a>
                                        </li>
                                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#" aria-label="Next">
                                                <span aria-hidden="true">»</span>
                                                <span class="sr-only">Next</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div v-if="display === 'tree'" class="card">
                        <div class="card-body min_table list_key md_npc_list_tree">
                        <div class="table_minwidth">
                            <div class="table table-striped">
                                <div class="thead-dark">
                                    <div class="md-top">
                                        <div class="w1"><span class="visually-hidden">Toggle</span></div>
                                        <div class="w1">Tên thiết bị</div>
                                        <div class="w2">Serial</div>
                                        <div class="w2">Kiểu</div>
                                        <div class="w2">Dòng điện</div>
                                        <div class="w2">Cấp chính xác</div>
                                        <div class="w2">Hãng sản xuất</div>
                                        <div class="w2">Năm sản xuất</div>
                                        <div class="w2">Vị trí lắp đặt</div>
                                        <div class="w2">Khu vực</div>
                                        <div class="w2">Trạm</div>
                                        <div class="w2">Ngăn lộ</div>
                                    </div>
                                </div>
                                <div class="main_table">
                                    <div class="main_table_1" v-for="(parent, parentIndex) in tree" v-bind:key="parent.id">
                                        <div class="main_table_table">
                                            <div class="main_table_2" v-on:click="parent.toggleStatus = !parent.toggleStatus">
                                                <div class="content2">
                                                    <span class="icon">
                                                        <i v-if="!parent.children.length" class="dripicons-wrong font-16"></i>
                                                        <i v-if="parent.children.length && parent.toggleStatus === false" class="ion ion-md-arrow-dropdown"></i>
                                                        <i v-if="parent.children.length && parent.toggleStatus === true" class="ion ion-md-arrow-dropright"></i>
                                                    </span>{{ parent.name }}
                                                </div>
                                            </div>
                                            <div class="main_table_2_ct" v-if="parent.toggleStatus" v-for="(city, cityIndex) in parent.children" v-bind:key="city.id">
                                                <div class="main_table_2_content">
                                                    <div class="main_table_2_city" v-on:click="toggleCityItem(city)">
                                                        <div class="city">
                                                            <span class="icon">
                                                                <i v-if="city.id !== toggleCity" class="ion ion-md-arrow-dropdown"></i>
                                                                <i v-else class="ion ion-md-arrow-dropright"></i>
                                                            </span>
                                                            {{  city.name }} <i v-if="loading === city.id" class="fas fa-spinner fa-spin"></i>
                                                        </div>
                                                    </div>
                                                    <div class="main_table_2_city_ct" v-if="city.id === toggleCity"
                                                         v-for="(station, stationIndex) in city.children" v-bind:key="station.id">
                                                        <div class="main_table_3">
                                                            <div class="main_table_3_ct">
                                                                <div class="content3" v-on:click="toggleStationItem(station, parentIndex, cityIndex,stationIndex)">
                                                                    <span class="icon">
                                                                        <i v-if="station.id !== toggleStation" class="ion ion-md-arrow-dropdown"></i>
                                                                        <i v-else class="ion ion-md-arrow-dropright"></i>
                                                                    </span>
                                                                    {{  station.name }} <i v-if="loading === station.id" class="fas fa-spinner fa-spin"></i>
                                                                </div>
                                                            </div>
                                                            <div class="main_table_2_city_ct" v-if="station.id === toggleStation"
                                                                 v-for="(block, blockIndex) in blockData.children">
                                                                <div class="main_table_3">
                                                                    <div class="main_table_3_ct">
                                                                        <div class="content4" v-on:click="toggleBlockItem(block)">
                                                                            <span class="icon">
                                                                                <i v-if="block.id !== toggleBlock" class="ion ion-md-arrow-dropdown"></i>
                                                                                <i v-else class="ion ion-md-arrow-dropright"></i>
                                                                            </span>
                                                                            {{ block.name }} <i v-if="loading === block.id" class="fas fa-spinner fa-spin"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="main_table_2_city_ct" v-if="block.id === toggleBlock"
                                                                         v-for="(device, deviceIndex) in deviceData.children">
                                                                        <div class="main_table_3">
                                                                            <div class="main_table_3_ct">
                                                                                <div class="content5">
                                                                                    <div class="w1"></div>
                                                                                    <div class="w1">{{ device.model}}</div>
                                                                                    <div class="w2"></div>
                                                                                    <div class="w2">{{ device.equipmentTypeName}}</div>
                                                                                    <div class="w2"></div>
                                                                                    <div class="w2"></div>
                                                                                    <div class="w2">{{ device.manufacturerName}}</div>
                                                                                    <div class="w2"></div>
                                                                                    <div class="w2"></div>
                                                                                    <div class="w2">{{ device.cityName}}</div>
                                                                                    <div class="w2">{{ device.stationName}}</div>
                                                                                    <div class="w2">{{ device.blockingRoadName}}</div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            display: 'tree', // default display
            tree: [],
            items: [],
            toggleCity: 0,
            toggleStation: 0,
            toggleBlock: 0,
            toggleDevice: 0,
            blockData : [],
            deviceData: [],
            allDevices:[],
            loading: 0
        }
    },
    created() {
        axios.get('/admin/device/master-tree').then((response) => {
            response.data.forEach((item) => {
                item.toggleStatus = false;
                this.tree.push(item);
            });
        });
        axios.get('/admin/device/all-devices').then((response) => {
            this.allDevices = response.data;
        });
    },
    methods: {
        toggleCityItem(item) {
            if(item.children === null) {
                this.loading = item.id;
                const url = '/admin/device/stations?city=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    this.loading = null;
                    this.toggleCity = item.id;
                });
            } else {
                if(this.toggleCity === item.id) {
                    this.toggleCity = null;
                } else {
                    this.toggleCity = item.id;
                }
            }
        },
        toggleStationItem(item,  parentIndex, cityIndex,stationIndex) {
            if(typeof item.children === 'undefined') {
                this.loading = item.id;
                item.children = [];
                const url = '/admin/device/blocks?station=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    this.tree[parentIndex].children[cityIndex].children[stationIndex] = item;
                    this.blockData = item;
                    this.loading = null;
                    this.toggleStation = item.id;
                });
            } else {
                if(this.toggleStation === item.id) {
                    this.toggleStation = null;
                } else {
                    this.toggleStation = item.id;
                }
            }
        },
        toggleBlockItem(item,  parentIndex, cityIndex,stationIndex) {
            if(typeof item.children === 'undefined') {
                this.loading = item.id;
                item.children = [];
                const url = '/admin/device/block-devices?block=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    this.deviceData = item;
                    this.loading = null;
                    this.toggleBlock = item.id;
                });
            } else {
                if(this.toggleBlock === item.id) {
                    this.toggleBlock = null;
                } else {
                    this.toggleBlock = item.id;
                }
            }
        },
        changeTableView() {
            this.display = this.display === 'list' ? 'tree' : 'list';
        }
    }
}
</script>
