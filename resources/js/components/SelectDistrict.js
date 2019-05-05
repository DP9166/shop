const addressData = require('china-area-data/v3/data');

import _ from 'lodash';

Vue.component('select-district', {
   // 定义组件的属性
   props: {
       initValue: {
           type: Array, // 格式是数组
           default: () => ([]), // 默认是空数组
       }
   },
   // 定义了这个组件内的数据
   data() {
       return {
           provinces: addressData['86'],
           cities: {}, // 城市列表
           districts: {}, // 地区列表
           provinceId: '', // 当前选中的省
           cityId: '', // 当前选中的城市
           districtId: '', // 当前选中的区
       };
   },
    watch: {
       // 当选中的省发生改变市触发
       provinceId(newVal) {
           if (!newVal) {
               this.cities = {};
               this.cityId = '';
               return;
           }
           // 将城市列表设为当前省下的城市
           this.cities = addressData[newVal];
           if (!this.cities[this.cityId]) {
               this.cityId = '';
           }
       },
        // 当选择的市发生改变时触发
        cityId(newVal) {
           if (!newVal) {
               this.districts = {};
               this.districtId = '';
               return;
           }
           // 将地区列表设为当前城市下的地区
           this.districts = addressData[newVal];
           if (!this.districts[this.districtId]) {
               this.districtId = '';
           }
        },
        districtId() {
           this.$emit('change', [
               this.provinces[this.provinceId],
               this.cities[this.cityId],
               this.districts[this.districtId]
           ]);
        }
    },
    created() {
       this.setFromValue(this.initValue);
    },
    methods: {
       setFromValue(value) {
           value = _.filter(value);
           if (value.length === 0) {
               this.provinceId = '';
               // 待续....
           }
       }
    }
});