import 'nStyle/collapse.scss';

export default {
  name: 'el-collapse',
  template: `<div class='el-collapse' >
    <div @click.stop.prevent='openCol'>
      <slot name='title'></slot>
    </div>
    <div class="collapse-wrap">
      <slot name='content'></slot>
    </div>
  </div>`,
  methods: {
    openCol() {
      const cEl = this.$el.lastElementChild;
      const elType = Object.prototype.toString.call(cEl);
      // element is exist
      if (elType.indexOf('HTMLDivElement') > -1) {
        const { height } = window.getComputedStyle(cEl);
        if (parseFloat(height) <= 0) {
          const cHeight = window.getComputedStyle(cEl.firstChild).height;
          cEl.style.height = cHeight;
        } else {
          cEl.style.height = '0px';
        }
      } else {
        /* eslint-disable no-throw-literal */
        throw 'v-el-collapse\'s content must be exist, don\'t use v-if';
        /* eslint-enable no-throw-literal */
      }
    }
  }
};
