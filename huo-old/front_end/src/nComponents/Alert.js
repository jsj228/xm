import 'nStyle/alertCompent.scss';
import Vue from 'vue';
import tplCreater from './tplCreater';

Vue.component('my-alert', {
  name: 'Alert',
  template: tplCreater('AlertHTML'),

  props: ['showStatus'],

  methods: {
    hide() {
      // 关闭弹框 回掉
      this.$emit('update:showStatus', false);

      this.$emit('callback');
    }
  }
});

export default {};
