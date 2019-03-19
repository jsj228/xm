let tpl=` 

  <div class="block">
   <el-pagination
      @size-change="handleSizeChange"
      @current-change="handleCurrentChange"
      :current-page="currentPage4"
      :page-sizes="[100, 200, 300, 400]"
      :page-size="100"
      :total="400"
      layout="total, sizes, prev, pager, next, jumper"
      >
  </el-pagination>
  </div>
`;
export default {
  name: 'page',
  template:tpl,
  props: ['parentData'],
  data() {
    return {
      currentPage1: 5,
      currentPage2: 5,
      currentPage3: 5,
      currentPage4: 4,
    };
  },
  mounted() {
   
  },
  methods: {
    //分页element
    handleSizeChange(val) {
      console.log(`每页 ${val} 条`);
    },
    handleCurrentChange(val) {
      console.log(`当前页: ${val}`);
    },
  },
  // watch: {
  //   data() {
  //     this.$nextTick(() => {
  //       this.getTbodyTds();
  //     });
  //   }
  // }
};
