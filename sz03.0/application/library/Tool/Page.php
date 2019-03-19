<?php
class Tool_Page{
	/**
	 * 当前页码
	 */
	public $mPage = 1;

	/**
	 * 每页显示条数
	 */
	public $mPagesize;

	/**
	 * 最大页码
	 */
	public $mPageMax;
	public $mCnt = 0;

	/**
	 * 构造函数
	 * @param int $pCnt 总记录数
	 * @param int $pPagesize 每页显示条数
	 */
	function __construct($pCnt, $pPagesize = 15){
		$this->mCnt = $pCnt;
		$pPagesize = $pPagesize?$pPagesize:15;
		if($this->mPageMax = max(1, ceil($pCnt / $this->mPagesize = $pPagesize))){
			if(isset($_GET['p']) && ($this->mPage = abs($_GET['p'])) && ($this->mPageMax < $this->mPage)){
				$this->mPage = $this->mPageMax;
			}
			else
			{
				preg_match('#/page/(\d+)#i', REDIRECT_URL, $m);
				$m and $this->mPage = $m[1];
			}
		}
		$this->mPage || $this->mPage = 1;
		// 指定跳转页码 lbj 20170802
	}

	/**
	 * 分页
	 */
	function limit(){
		return (($this->mPage - 1) * $this->mPagesize).','.$this->mPagesize;
	}

	/**
	 * 处理连接
	 */
	private $_href = '';
	function _default_href($pHref=''){
		if($pHref) return $this->_href = $pHref;
		if(!$this->_href){
			$tUrl = isset($_SERVER['REDIRECT_URL'])? $_SERVER['REDIRECT_URL'].$_SERVER['REQUEST_URI']: $_SERVER['REQUEST_URI'];
			$tUrl = strip_tags(urldecode($tUrl));
			list($tUri, $queryStr) = explode('?', $tUrl);
			if(stripos($tUri, '/page/'))
			{
				$tUri = preg_replace('#/page/(\d+)#', "", $tUri);
			}
			
			$this->_href = ' <li><a href="'.urldecode($tUri).'/page/%d'.(isset($queryStr)?'?'.$queryStr:'').'">%s</a></li> ';
		}
		return $this->_href;
	}

	/**
	 * 显示分页
	 */
	function show($pHref=''){
		$this->_default_href($pHref);
		if($this->mPageMax == 1) return '';
		$tPage = array();
		# 当前之前
		$tMax = $this->mPageMax - $this->mPage > 5? 5: 10 - $this->mPageMax + $this->mPage;
		for ($i = 0; $i < $tMax; $i++) {
			if(($tNum = $this->mPage - $i) < 1) break;
			$tPage[] = $tNum;
		}
		$tPage && sort($tPage);
		# 当前之后
		($tMax = 10 - ($tCnt = count($tPage))) < 5 && $tMax = 5;
		($tMax > ($this->mPageMax - $this->mPage)) && $tMax = $this->mPageMax - $this->mPage;
		for ($i = 0; $i < $tMax; $tPage[] = ++$i + $this->mPage);
		# 渲染分页
		$tHtml = '<div class="page"><ul class="pageUl clear">';
		# 上一页
		$tHtml .= $this->_make_href(1, $GLOBALS['MSG']['NUMBER_HOME']);//首頁
		$tHtml .= $this->_make_href($this->mPage-1, ' &lt; ');
		# 页码
		foreach ($tPage as $v1) $tHtml .= $this->_make_href($v1, $v1);
		# 下一页
		$tHtml .= $this->_make_href($this->mPage+1, ' &gt; ');
		$tHtml .= $this->_make_href($this->mPageMax, $GLOBALS['MSG']['NUMBER_FOOR']);//尾頁
		return $tHtml.'</UL></div>';
	}

	/**
	 * 制造链接(逻辑写的不好，代码比较丑)
	 * @param $p
	 * @param $t
	 * @return string
	 */
	function _make_href($p, $t){
		if($p > $this->mPageMax) {
			$tReplace = array('%d'=>$this->mPageMax, '%s'=>$t);
		} elseif(1 > $p){
			$tReplace = array('%d'=>1, '%s'=>$t);
		}elseif($this->mPage == $p){
			$tReplace = array('">' => '" class="pageActive">', '%d'=>$p, '%s'=>$t);
		} else {
			$tReplace = array('%d'=>$p, '%s'=>$t);
		}
		return strtr($this->_href, $tReplace);
	}
}
