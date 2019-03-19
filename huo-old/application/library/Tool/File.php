<?php
/**
 * 文件操作
 */
class Tool_File {

	/**
	 * 导出csv
	 * @param  string $filename 文件名
	 * @param  string $data     数据
	 */
	function exportCsv($filename, $data)   
	{   
		header("Content-type:text/csv");
		header("Content-Disposition:attachment;filename=".$filename);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');
		exit($data);
	}

}