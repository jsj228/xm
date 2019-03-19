export default {
  name: 'scrollTable',
  template: `<div><slot name="thead"></slot><slot name="tbody"></slot></div>`,
  props: ['data'],
  data() {
    return {
      theadTds: '',
      tbodyFirstTds: ''
    };
  },
  mounted() {
    let tables = this.$el.getElementsByTagName('table');
    if (tables.length > 0) {
      let thead = tables[0];
      let tbody = tables[1];
      let theadTr = thead.getElementsByTagName('tr');
      if (theadTr.length > 0) {
        let tds = theadTr[0].getElementsByTagName('td');
        let ths = theadTr[0].getElementsByTagName('th');
        if (tds.length > 0) {
          this.theadTds = [...tds];
        }
        if (ths) {
          this.theadTds = [...ths];
        }
      }
      //
      let tbodyFirstTr = tbody.getElementsByTagName('tr');
      //
      if (tbodyFirstTr.length > 0) {
        this.tbodyFirstTds = [...tbodyFirstTr[0].getElementsByTagName('td')];
      }
    }

    // this.getTbodyTds();
    //
    window.addEventListener('resize', () => {
      this.getTbodyTds();
    }, false);
  },
  methods: {
    setTheadStyle() {
      if (this.tbodyFirstTds.length > 0) {
        this.tbodyFirstTds.forEach((td, index) => {
          let style = window.getComputedStyle(td);
          // console.log(style.width);
          this.theadTds[index].style.minWidth = style.width;
        });
      }
    },
    getTbodyTds() {
      let tables = this.$el.getElementsByTagName('table');
      if (tables.length > 0) {
        let tbody = tables[1];
        let tbodyFirstTr = tbody.getElementsByTagName('tr');

        //
        if (tbodyFirstTr.length > 0) {
          this.tbodyFirstTds = [...tbodyFirstTr[0].getElementsByTagName('td')];
          this.setTheadStyle();
        }
      }
    }
  },
  watch: {
    data() {
      this.$nextTick(() => {
        this.getTbodyTds();
      });
    }
  }
};
