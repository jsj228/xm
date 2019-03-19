let tpl=` 
  <div class="block">
   <h2 >父组件的值{{parentData}}</h2>

  </div>
`;
export default {
  name: 'page',
  template:tpl,
  props: ['parentData'],
  data() {
    return {
      
    };
  },
  mounted() {
   
  },
  methods: {

    // currentPage4(){
    //   console.log('currentPage4')
    // }
  },
  // watch: {
  //   data() {
  //     this.$nextTick(() => {
  //       this.getTbodyTds();
  //     });
  //   }
  // }
};
