import "nStyle/alert.header.scss";
import Vue from 'vue';
import tplCreater from './tplCreater';

Vue.component('alert-header', {
  name: 'Aelertheader',
  template: tplCreater('AlertHeader'),

  props: '',

  methods: {
    alertBack() {
      // 关闭弹框 回掉
      this.$emit('alertback');
    }
  }
});

export default {};
