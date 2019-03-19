<template>
    <div class="pages">
        <button class="minBtn bg666" @click="pageFun('left')"><</button>
        <span>
            <button v-for="i in 15"  @click="pageFun('',i)" v-text="i" class="minBtn bgRed">
            </button>
        </span>
        <button class="minBtn bgRed"  @click="pageFun('right')">></button>
  </div>
</template>
<script>
  
  export default {
    name: 'pages',
    props:[    //使用props来接受父组件传过来的数据
		'pages'
	],
	filters:{
		join(args){
			return args.join(',')
		}
    },
    data () {
        return {}
    },
    created () {
    },
    mounted () { 
        
    },
    methods: {
    },
    computed:{
    }

}
</script>
<style scoped>
    
</style>
        
