let tpl=`
<div class="page">
    <nav class="boot-nav">
        <ul class="pagination boot-page">
            <li>
                <a  aria-label="Previous" @click="onFirstClick()">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <li>
                <a  aria-label="Next" @click="onPrevClick()">
                    <span aria-hidden="true">‹</span>
                </a>
            </li>
            
            <li v-for="i in pages" :class="activeNum === i ? 'active' : ''">
                <a  v-text="i" @click="onPageClick(i)"></a>
            </li>

            <li>
                <a  aria-label="Next" @click="onNextClick()">
                    <span aria-hidden="true">›</span>
                </a>
            </li>
            <li>
                <a  aria-label="Next" @click="onLastClick()">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
        <div class="page-total">
            共 <span v-text="pageTotal"></span> 页
        </div>
    </nav>
    <select class="form-control boot-select" v-model="len">
        <option v-for="arr in lens" :value="arr" v-text="arr" :selected="activeNum === 0 ? true : false"></option>
	</select>

</div>`;
export default {
  name: 'page',
  template:tpl,
  props: {
	// 是否请求服务器端数据 	
	async: {type: Boolean, default: false},
	// 页码
	pages: {type:Number,default: 10},
	// 每页显示个数
    len: {type:Number,default: 10},
	 // 显示个数数组
	lens: {type: Array},
	// 表格数据（数组）
	data: {type: Array},
	 // AJAX地址
    url: {type: String},
    // 显示页数
    pageLen: {type: Number},
    // 总页数 
    pageTotal: {type: Number},
    // 参数内容
    param: {type: Object}
  },
  data() {
    return {
      activeNum: 1
    };
  },
  methods: {
    // 点击页码刷新数据
    onPageClick (index) {
        console.log('onPageClick');
        this.activeNum = index
    },
    // 上一页
    onPrevClick () {
      console.log('onPrevClick');
        // 当前页是否为当前最小页码
        if (this.activeNum > 0) {
            this.activeNum = this.activeNum - 1
        } else {
            if (this.pages[0] !== 1) {
                let newPages = []

                for (let i = 0; i < this.pages.length; i++) {
                    newPages[i] = this.pages[i] - 1
                }
                this.pages = newPages
                this.getData()
            }
        }
    },
    // 下一页
    onNextClick () {
        console.log('onNextClick');
        // 当前页是否为当前最大页码
        if (this.activeNum < this.pages.length - 1) {
            this.activeNum = this.activeNum + 1
        } else {
            if (this.pages[this.pages.length - 1] < this.pageTotal) {
                let newPages = []
                for (let i = 0; i < this.pages.length; i++) {
                    newPages[i] = this.pages[i] + 1
                }
                this.pages = newPages
                this.getData()
            }
        }
    },

    // 第一页
    onFirstClick () {
        console.log('onFirstClick')
        if (this.pages[0] === 1) {
            this.activeNum = 0
        } else {
            let originPage = []

            for (let i = 1; i <= this.pageLen; i++) {
                originPage.push(i)
            }
            this.pages = originPage
            this.activeNum === 0 ? this.getData() : this.activeNum = 0
        }
    },
    // 最后一页
    onLastClick () {
      console.log('onLastClick')
        if (this.pageTotal <= this.pageLen) {
            this.activeNum = this.pages.length - 1
        } else {
            let lastPage = []

            for (let i = this.pageLen - 1; i >= 0; i--) {
                lastPage.push(this.pageTotal - i)
            }

            this.pages = lastPage
            this.activeNum === this.pages.length - 1 ? this.getData() : this.activeNum = this.pages.length - 1
        }
    },

    // 获取页码
    getPages () {
        this.pages = []
        if (!this.async) {
			console.log(this.data.length,'0000');
            this.pageTotal = Math.ceil(this.data.length / this.len)
        }
        // 比较总页码和显示页数
        if (this.pageTotal <= this.pageLen) {
            for (let i = 1; i <= this.pageTotal; i++) {
                this.pages.push(i)
            }
        } else {
            for (let i = 1; i <= this.pageLen; i++) {
                this.pages.push(i)
            }
        }
    },

    // 页码变化获取数据
    getData () {
		console.log(this.async);
        if (!this.async) {
            let len = this.len,
                pageNum = this.pages[this.activeNum] - 1,
                newData = [];

            for (let i = pageNum * len; i < (pageNum * len + len); i++) {
                this.data[i] !== undefined ? newData.push(this.data[i]) : ''
			}
            // this.$dispatch('data', newData)
        } else {
            this.param.active = this.pages[this.activeNum]
            this.param.len = this.len

            this.$http({
                url: this.url, 
                method: 'POST',
                data: this.param
            })
            .then(function (response) {
                this.pageTotal = response.data.page_num

                if (this.pages.length !== this.pageLen || this.pageTotal < this.pageLen) {
                    this.getPages()
                }

                if (!response.data.data.length) {
                    this.activeNum = this.pageTotal - 1
                }
                // this.$dispatch('data', response.data.data)
            })
        }
    },
    // 刷新表格
    refresh () {	this.getData()	},
    // 重置并刷新表格
    refresh2 () {
        this.pages = [1]
        this.activeNum === 0 ? this.getData() : this.activeNum = 0
    }
},
  ready () {
    if (!this.async) {
        this.getPages()
    } 
    this.getData()
},
  watch: {
    len (newVal, oldVal) {
      if (!this.async) {
          this.getPages()
            if (this.activeNum + 1 > this.pages.length) {
                this.activeNum = this.pages.length - 1
            }
            this.getData()
        } else {
            this.refresh2()
        }
      },
  // 监测当前页变化
    activeNum(newVal, oldVal) {
        this.getData()
    }

  }
};