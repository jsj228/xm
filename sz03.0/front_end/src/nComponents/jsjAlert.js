import 'nStyle/alertCompent.scss';
import Vue from 'vue';
var alertObj = Vue.extend({
    template:`<div v-cloak v-if="showStatus">
              <div class="mask_contain" flex="main:center cross:center" >
                <div class="alert_content relative_dom" flex="dir:top main:center cross:center">
                  <span class="close_btn" @click="hide">×</span>
                  <slot name="headerText"></slot>
                  <div class="btn_line">
                      <button @click="clickBtn" class="orange500_bg" style="width:120px;">
                        <slot name="Ok"></slot>
                      </button>
                      <button @click="hide" class="orange500_bg" style="width:120px;">
                        <slot name="No"></slot>
                      </button>
                  </div>
                </div>
              </div>
            </div>`, //template就是组件的模板
    data: function() { //子组件内的数据必须是一个函数
        return {
            // tplData: '数据信息',
        }
    },
    props: ['showStatus'],
    methods: {
      hide() { //点击执行函数hidebox
        this.$emit('hidebox');
      },
      clickBtn(){//点击执行函数callback
        this.$emit('callback');
      }
  }
});
Vue.component('jsj-alert', alertObj);