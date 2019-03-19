import Vue from 'vue';

// <li class="li_none" v-if="so == true"><a href="javascript:void(0);">......</a></li>
// <li v-if="so == true|| end == true" :class="{active_li:nums == all}"><a href="javascript:void(0);" @click="jumps(all , all-1)">{{all}}</a></li>
Vue.component('pages', {
  template: `<div class="container_page">
    <ul class="pageSplie">
      <li :class="{active_li:types == 'first'}" @click="jumps(1 , 0)"><a href="javascript:void(0);">{{homeTitle}}</a></li>
      <li><a href="javascript:void(0);" @click="jumpGo('back')"><</a></li>
      <li v-for="(i , index) in pages" :class="{active_li:nums == index}" v-if="i > firstPage"><a href="javascript:void(0);" @click="jumps(i , index)">{{i}}</a></li>
      <li class="li_none" v-if="so == true"><a href="javascript:void(0);">......</a></li>
      <li v-if="so == true || end == true" :class="{active_li:nums == all}"><a href="javascript:void(0);" @click="jumps(all , all-1)">{{all}}</a></li>
      <li><a href="javascript:void(0);" @click="jumpGo('go')">></a></li>
      <li @click="jumps(all , all-1)" :class="{active_li:types == 'last', last_page: true}"><a href="javascript:void(0);">{{lastTitle}}</a></li>
      <li class="li_none_right last_page">{{jumpToTitle}}</li>
      <li class="li_text"><input type="number" v-model="editJump" class="pages_text" @blur="vlaues" @keydown="enterSubmit($event)"></li>
      <li class="li_none_left">{{pageName}}</li>
      <li class="active_li"><a href="javascript:void(0);"  @click="jumps(editJump > all ? all : editJump,editJump > all ? all-1 : editJump-1, 'jump')">GO</a></li>
      <div class="clear"></div>
    </ul>
  </div>`,
  data() {
    return {
      count: 1,
      nums: 0,
      so: true,
      end: false,
      lastPage: '',
      firstPage: 0,
      editJump: '',
      currPage: 1,
      types: 'first'
    };
  },
  computed: {
    pages() {
      let pages = '';
      if (this.all <= 5) {
        this.so = false;
        this.lastPage = this.all;
        pages = this.lastPage;
      }
      else {
        this.so = true;
        if (this.lastPage == '') {
          this.lastPage = 4;
        }
        else if (this.lastPage > this.all - 2 && this.lastPage != this.all) {
          this.so = false;
          this.end = true;
        }
        else if (this.lastPage == this.all) {
          this.so = false;
          this.end = false;
        }
        this.lastPage = this.lastPage;
        this.firstPage = this.lastPage - 4;
        pages = this.lastPage;
      }
      return pages;
    }
  },
  props: {
    all: {
      type: [String, Number]
    },
    currentpage: {
      type: [String, Number]
    },
    current: [String, Number],
    homeTitle: String,
    lastTitle: String,
    pageName: String,
    jumpToTitle: String
  },
  methods: {
    vlaues() {
      this.editJump = this.editJump.replace(/[\.]/, '');
      // if (this.editJump > this.all) {
      //   this.editJump = this.all;
      // }
    },
    jumpGo(type) {
      if (type == 'go' && this.currPage < this.all) {
        this.currPage = parseInt(this.currPage) + 1;
      }
      else if (type == 'back' && this.currPage > 1) {
        this.currPage = parseInt(this.currPage) - 1;
      }
      this.jumps(this.currPage, this.currPage - 1);
    },
    jumps(i, index, jump) {
      if (i == 1) {
        this.types = 'first';
      }
      else if (i == this.all) {
        this.types = 'last';
      }
      else {
        this.types = '';
      }
      if (i != '' && index != null) {
        // this.currPage = i;
        this.nums = index;
        // console.log('firstPage=='+this.firstPage);
        // console.log('lastPage=='+this.lastPage);
        // console.log('currPage=='+this.currPage);
        // console.log('i=='+i);
        if (i == this.currPage && jump == 'jump') {
          // console.log('cur=------------------');
          this.lastPage = this.lastPage;
          this.firstPage = this.firstPage;
          // console.log('cur=---------------'+this.currPage);
        }
        else {
          this.currPage = i;
          if (i == this.lastPage) {
            // console.log('in1=-------------------');
            if (this.all > this.lastPage) {
                this.lastPage = i + 1;
            }
          }
          else if (i == this.all) {
            // console.log('in2=-------------------');
            this.lastPage = this.all;
          }
          else if (index == this.firstPage && this.firstPage > 0) {
            // console.log('in3=-------------------');
            this.lastPage = this.lastPage - 1;
            if (this.firstPage == 0) {
              this.firstPage = 0;
            }
            else {
              this.firstPage = index - 1;
            }
          }
          else if (i > this.lastPage) {
            // console.log('in4=-------------------');
            this.lastPage = parseInt(i) + 1;
            this.firstPage = this.all - i;
          }
          else if (i < this.lastPage) {
            // console.log('in5=-------------------');
            if (i > 4) {
              this.lastPage = this.lastPage - 1;
              this.firstPage = this.firstPage - 1;
            }
            else {
              this.lastPage = 4;
              this.firstPage = 0;
            }
          }
        }
        this.$emit('get-tabs', i);
        // console.log('i==last=='+i);
        // console.log('firstPage==last=='+this.firstPage);
        // console.log('lastPage==last=='+this.lastPage);
        // console.log('currPage==last=='+this.currPage);
      }
      else {
        return false;
      }
    },
    enterSubmit(event){
      let keyCode = event.keyCode || event.which;
      if (keyCode === 13) {
        this.jumps(this.editJump > this.all ? this.all : this.editJump,this.editJump > this.all ? this.all-1 : this.editJump-1, 'jump')
      }
    }
  },
  watch: {
    current() {
      if (this.currPage != this.current) {
        this.jumps(this.current, this.current - 1);
      }

    },
    currentpage() {
      if (this.currPage != this.currentpage) {
        this.jumps(this.currentpage, this.currentpage - 1);
      }
    }
  }
});
