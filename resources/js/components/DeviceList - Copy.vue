<template>
    <div>
        <div class="row align-items-center m-b-20">
            <div class="col-md-8 col-lg-8">
                <h4 class="page-title">Danh sách thiết bị thí nghiệm hiệu chỉnh</h4>
            </div>
            <div class="col-md-4 col-lg-4">
                <div class="widgetbar text-right">
                    <button class="btn btn-primary-rgba show_list list_tree"><i class="fa fa-list mr-2"></i>Dạng list</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card m-b-30">
                    <div v-if="display=='list'">
                        <div  class="card-body min_table list_list">
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
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    <tr>
                                        <td>NPC</td>
                                        <td>BẮC GIANG</td>
                                        <td>110kV Đức Thắng</td>
                                        <td>Hệ thống SCADA</td>
                                        <td>RTU</td>
                                        <td>232fdg</td>
                                        <td>RTU560</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                        <td>Biên bản</td>
                                    </tr>
                                    </tbody>
                                </table>
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
                                                        <i v-if="!parent.children.length" class="dripicons-wrong"></i>
                                                        <i v-else class="ion ion-md-arrow-dropdown"></i>
                                                    </span>{{ parent.name }}
                                                </div>
                                            </div>
                                            <div class="main_table_2_ct" v-if="parent.toggleStatus" v-for="(city, cityIndex) in parent.children" v-bind:key="city.id">
                                                <div class="main_table_2_content">
                                                    <div class="main_table_2_city" v-on:click="toggleCityItem(city)">
                                                        <div class="city">
                                                            <span class="icon"><i class="ion ion-md-arrow-dropdown"></i></span>
                                                            {{  city.name }}
                                                        </div>
                                                    </div>
                                                    <div class="main_table_2_city_ct" v-if="city.id === toggleCity"
                                                         v-for="(station, stationIndex) in city.children" v-bind:key="station.id">
                                                        <div class="main_table_3">
                                                            <div class="main_table_3_ct">
                                                                <div class="content3" v-on:click="toggleStationItem(station, parentIndex, cityIndex,stationIndex)">
                                                                    <span class="icon"><i class="ion ion-md-arrow-dropdown"></i></span>
                                                                    {{  station.name }} qwe
                                                                </div>
                                                            </div>
                                                            <div class="main_table_2_city_ct" v-if="station.id === toggleStation"
                                                                 v-for="(block, blockIndex) in blockData.children">
                                                                <div class="main_table_3">
                                                                    <div class="main_table_3_ct">
                                                                        <div class="content4" v-on:click="toggleBlockItem(block)">
                                                                            <span class="icon"><i class="ion ion-md-arrow-dropdown"></i></span>
                                                                            {{ block.name }}
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
            deviceData: []
        }
    },

    created() {
        axios.get('/admin/device/master-tree').then((response) => {
            response.data.forEach((item) => {
                item.toggleStatus = false;
                this.tree.push(item);
            });
        });
    },

    methods: {

        toggleCityItem(item) {
            if(this.toggleCity === item.id) {
                this.toggleCity = null;
            } else {
                this.toggleCity = item.id;
            }
            if(item.children === null) {
                item.children = [];
                const url = '/admin/device/stations?city=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    console.log(item);
                });
            }
        },
        toggleStationItem(item,  parentIndex, cityIndex,stationIndex) {
            console.log('toggleStationItem');
            console.log(item);
            if(this.toggleStation === item.id) {
                this.toggleStation = null;
            } else {
                this.toggleStation = item.id;
            }
            if(typeof item.children === 'undefined') {
                item.children = [];
                const url = '/admin/device/blocks?station=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    this.tree[parentIndex].children[cityIndex].children[stationIndex] = item;
                    this.blockData = item;
                });
            }
        },

        toggleBlockItem(item,  parentIndex, cityIndex,stationIndex) {
            console.log('toggleBlockItem');
            if(this.toggleBlock === item.id) {
                this.toggleBlock = null;
            } else {
                this.toggleBlock = item.id;
            }
            if(typeof item.children === 'undefined') {
                item.children = [];
                const url = '/admin/device/block-devices?block=77965=' + item.id;
                axios.get( url).then((response) => {
                    item.children = response.data;
                    this.deviceData = item;
                });
            }
        }

    }

}
</script>
