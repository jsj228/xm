
<?php
/**
 * Debug Output
 *
 */
class Tool_Debug {
	# debug调试输出文件
	private static $_debug_file = '/tmp/debug.log';

    /**
    * 浏览器友好的变量输出
    * @param mixed $var 变量
    * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
    * @param string $label 标签 默认为空
    * @param boolean $strict 是否严谨 默认为true
    * @return void|string
    */
    static function dump($var, $echo=true, $label=null, $strict=true) {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }

	
	/**
	 * ajax类请求输出
	 * @param	mixed	$data
	 * @param	string	$debug_file
	 */
	static function ajax($data, $debug_file=false) {
		if ($debug_file) {
			self::$_debug_file	= $debug_file;
		}
		
		if (is_resource($data)) {
			return file_put_contents(self::$_debug_file, "Debug:资源类型\n");
		}
		
		return file_put_contents(self::$_debug_file, json_encode($data)."\n");
	}

}
