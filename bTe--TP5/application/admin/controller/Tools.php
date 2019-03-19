<?php
namespace app\admin\controller;

use think\Cache;
use think\Db;

class Tools extends Admin
{
	public function index()
	{
		$size = $this->getDirSize('./../runtime/');
        $unit=array('b','Kb','Mb','Gb','Tb','Pb');
        $this->assign('cacheSizeStr', round($size / pow(1024, $i = floor(log($size, 1024))), 2).' '.$unit[$i]);
		$this->assign('cacheSize', round($size / pow(1024, $i = floor(log($size, 1024))), 2));
		return $this->fetch();
	}

	public function delcahe()
	{
		$size = $this->getDirSize('./../runtime/');
		$this->assign('cacheSize', round($size / pow(1024, 1), 2));
		return $this->fetch();
	}

	protected function getDirSize($dir)
	{
		$sizeResult = 0;
		$handle = opendir($dir);

		while (false !== $FolderOrFile = readdir($handle)) {
			if (($FolderOrFile != '.') && ($FolderOrFile != '..')) {
				if (is_dir($dir . '/' . $FolderOrFile)) {
					$sizeResult += $this->getDirSize($dir . '/' . $FolderOrFile);
				} else {
					$sizeResult += filesize($dir . '/' . $FolderOrFile);
				}
			}
		}

		closedir($handle);
		return $sizeResult;
	}

	//清理缓存
	public function delcache()
	{
		$dirs = array('./../runtime/');
		@(mkdir('runtime', 511, true));

		foreach ($dirs as $value) {
			$this->rmdirr($value);
		}

		@(mkdir('runtime', 511, true));
		$this->success('文件缓存清除成功！');
	}

	//清理Redis缓存
    public function delredis()
    {
        if(intval(session('admin_id')) !== 1){
//        	$this->error('权限不足，您不能清理 Redis 缓存！');
		}
        Cache::clear_prefix(config('cache')['prefix']);

        $this->success('Redis缓存清除成功！');
	}

	public function invoke()
	{
		
		$dirs = array('./../runtime/');
		@(mkdir('runtime', 511, true));

		foreach ($dirs as $value) {
			$this->rmdirr($value);
		}

		@(mkdir('runtime', 511, true));
	}

	protected function rmdirr($val)
	{
        $dirname =strval($val);
		if (!file_exists($dirname)) {
			return false;
		}

		if (is_file($dirname) || is_link($dirname)) {
			return unlink($dirname);
		}

		$dir = dir($dirname);

		if ($dir) {
			while (false !== $entry = $dir->read()) {
				if (($entry == '.') || ($entry == '..')) {
					continue;
				}

				$this->rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
			}
		}

		$dir->close();
		return rmdir($dirname);
	}

	public function dataExport()
	{
		die();
		redirect(__MODULE__.'/Tools/database?type=export');
	}

	public function dataImport()
	{
		die();
		redirect(__MODULE__.'/Tools/database?type=import');
	}

	public function database($type = NULL)
	{
		die();
		switch ($type) {
		case 'import':
			$path = realpath(DATABASE_PATH);
			$glob = self::FilesystemIterator($path);
			$list = array();
			for($i=0;$i<count($glob);$i++){
				$name=$glob[$i];
				$a = str_replace(".sql.gz","",$glob[$i]);
				$lv=explode("-",$a);
				if (preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql(?:\\.gz)?$/', $name)) {
					$name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
					$date = $name[0] . '-' . $name[1] . '-' . $name[2];
					$time = $name[3] . ':' . $name[4] . ':' . $name[5];
					$part = $name[6];
					
					$list[$i]['time']=strtotime($date." ".$time);
					$list[$i]['part']=$lv[2];
					$list[$i]['size']=filesize($path."/".$glob[$i])."B";
					$list[$i]['key']=$date." ".$time;
		    	}
			}
			break;

		case 'export':
			$Db = \Think\Db::getInstance();
			$list = $Db->query('SHOW TABLE STATUS');
			$list = array_map('array_change_key_case', $list);
			$title = '数据备份';
			break;

		default:
			$this->error('参数错误！');
		}

		$this->assign('meta_title', $title);
		$this->assign('list', $list);
		$this->display($type);
	}

	function FilesystemIterator($dir){
			die();
				//PHP遍历文件夹下所有文件 
			$handle=opendir($dir."."); 
			//定义用于存储文件名的数组 
			$array_file = array(); 
			while (false !== ($file = readdir($handle))) 
			{ 
			if ($file != "." && $file != "..") { 
			$array_file[] = $file; //输出文件名 
			} 
			} 
			closedir($handle); 
			for($i=0;$i<count($array_file);$i++){
				if(strstr($array_file[$i],'.sql.gz')){
					$date[]=$array_file[$i];
					}
				}

			  return $date;
		}
	public function optimize($tables = NULL)
	{
		die();
		if ($tables) {
			$Db = \Think\Db::getInstance();

			if (is_array($tables)) {
				$tables = implode('`,`', $tables);
				$list = $Db->query('OPTIMIZE TABLE `' . $tables . '`');

				if ($list) {
					$this->success('数据表优化完成！');
				} else {
					$this->error('数据表优化出错请重试！');
				}
			} else {
				$list = $Db->query('OPTIMIZE TABLE `' . $tables . '`');

				if ($list) {
					$this->success('数据表\'' . $tables . '\'优化完成！');
				} else {
					$this->error('数据表\'' . $tables . '\'优化出错请重试！');
				}
			}
		} else {
			$this->error('请指定要优化的表！');
		}
	}

	public function repair($tables = NULL)
	{
		die();
		if ($tables) {
			$Db = \Think\Db::getInstance();

			if (is_array($tables)) {
				$tables = implode('`,`', $tables);
				$list = $Db->query('REPAIR TABLE `' . $tables . '`');

				if ($list) {
					$this->success('数据表修复完成！');
				} else {
					$this->error('数据表修复出错请重试！');
				}
			} else {
				$list = $Db->query('REPAIR TABLE `' . $tables . '`');

				if ($list) {
					$this->success('数据表\'' . $tables . '\'修复完成！');
				} else {
					$this->error('数据表\'' . $tables . '\'修复出错请重试！');
				}
			}
		} else {
			$this->error('请指定要修复的表！');
		}
	}

	public function del($time = 0)
	{
		die();
		if ($time) {
			$name = date('Ymd-His', $time) . '-*.sql*';
			$path = realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR . $name;
			array_map('unlink', glob($path));

			if (count(glob($path))) {
				$this->success('备份文件删除失败，请检查权限！');
			} else {
				$this->success('备份文件删除成功！');
			}
		} else {
			$this->error('参数错误！');
		}
	}

	public function export($tables = NULL, $id = NULL, $start = NULL)
	{
		die();
		if (config('web_close')) {
			$this->error('请先关闭网站再备份数据库！');
		}

		if (IS_POST && !empty($tables) && is_array($tables)) {
			$config = array('path' => realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR, 'part' => 20971520, 'compress' => 1, 'level' => 9);
			$lock = $config['path'] . 'backup.lock';

			if (is_file($lock)) {
				$this->error('检测到有一个备份任务正在执行，请稍后再试！');
			} else {
				file_put_contents($lock, NOW_TIME);
			}

			is_writeable($config['path']) || $this->error('备份目录不存在或不可写，请检查后重试！');
			session('backup_config', $config);
			$file = array('name' => date('Ymd-His', NOW_TIME), 'part' => 1);
			session('backup_file', $file);
			session('backup_tables', $tables);
			$Database = new \OT\Database($file, $config);

			if (false !== $Database->create()) {
				$tab = array('id' => 0, 'start' => 0);
				$this->success('初始化成功！', '', array('tables' => $tables, 'tab' => $tab));
			} else {
				$this->error('初始化失败，备份文件创建失败！');
			}
		} else if (IS_GET && is_numeric($id) && is_numeric($start)) {
			$tables = session('backup_tables');
			$Database = new \OT\Database(session('backup_file'), session('backup_config'));
			$start = $Database->backup($tables[$id], $start);

			if (false === $start) {
				$this->error('备份出错！');
			} else if (0 === $start) {
				if (isset($tables[++$id])) {
					$tab = array('id' => $id, 'start' => 0);
					$this->success('备份完成！', '', array('tab' => $tab));
				} else {
					unlink(session('backup_config.path') . 'backup.lock');
					session('backup_tables', null);
					session('backup_file', null);
					session('backup_config', null);
					$this->success('备份完成！');
				}
			} else {
				$tab = array('id' => $id, 'start' => $start[0]);
				$rate = floor(100 * ($start[0] / $start[1]));
				$this->success('正在备份...(' . $rate . '%)', '', array('tab' => $tab));
			}
		} else {
			$this->error('参数错误！');
		}
	}

	public function import($time = 0, $part = NULL, $start = NULL)
	{
		die();
		if (config('web_close')) {
			$this->error('请先关闭网站再还原数据库！');
		}

		if (is_numeric($time) && is_null($part) && is_null($start)) {
			$name = date('Ymd-His', $time) . '-*.sql*';
			$path = realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR . $name;
			$files = glob($path);
			$list = array();

			foreach ($files as $name) {
				$basename = basename($name);
				$match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
				$gz = preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql.gz$/', $basename);
				$list[$match[6]] = array($match[6], $name, $gz);
			}

			ksort($list);
			$last = end($list);

			if (count($list) === $last[0]) {
				session('backup_list', $list);
				$this->success('初始化完成！', '', array('part' => 1, 'start' => 0));
			} else {
				$this->error('备份文件可能已经损坏，请检查！');
			}
		} else if (is_numeric($part) && is_numeric($start)) {
			$list = session('backup_list');
			$db = new \OT\Database($list[$part], array('path' => realpath(DATABASE_PATH) . DIRECTORY_SEPARATOR, 'compress' => 1, 'level' => 9));
			$start = $db->import($start);

			if (false === $start) {
				$this->error('还原数据出错！');
			} else if (0 === $start) {
				if (isset($list[++$part])) {
					$data = array('part' => $part, 'start' => 0);
					$this->success('正在还原...#' . $part, '', $data);
				} else {
					session('backup_list', null);
					$this->success('还原完成！');
				}
			} else {
				$data = array('part' => $part, 'start' => $start[0]);

				if ($start[1]) {
					$rate = floor(100 * ($start[0] / $start[1]));
					$this->success('正在还原...#' . $part . ' (' . $rate . '%)', '', $data);
				} else {
					$data['gz'] = 1;
					$this->success('正在还原...#' . $part, '', $data);
				}
			}
		} else {
			$this->error('参数错误！');
		}
	}

	public function excel($tables = NULL)
	{
		die();
		if ($tables) {
			Db::execute('set autocommit=0');
			Db::execute('lock tables ' . $tables . ' write');
			$rs = Db::table($tables)->select();
			$zd = Db::table($tables)->getTableFields();

			if ($rs) {
				Db::execute('commit');
				Db::execute('unlock tables');
			} else {
				Db::execute('rollback');
			}

			$xlsName = $tables;
			$xls = array();

			foreach ($zd as $k => $v) {
				$xls[$k][0] = $v;
				$xls[$k][1] = $v;
			}
			/*echo $xlsName;
			echo '<br>';
			echo $xls;
			echo '<br>';
			echo $rs;
			exit();*/
			$this->exportExcel($xlsName, $xls, $rs);
		} else {
			$this->error('请指定要导出的表！');
		}
	}

	public function exportExcel($expTitle, $expCellName, $expTableData)
	{
		die();
		import('Org.Util.PHPExcel') or die('22222');
		import('Org.Util.PHPExcel.Writer.Excel5');
		import('Org.Util.PHPExcel.IOFactory');
		$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
		$fileName = session('loginAccount') . date('_YmdHis');
		$cellNum = count($expCellName);
		$dataNum = count($expTableData);
		$objPHPExcel = new \PHPExcel();							
		$cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
		$objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time:' . date('Y-m-d H:i:s'));
		$i = 0;

		for (; $i < $cellNum; $i++) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
		}

		$i = 0;

		for (; $i < $dataNum; $i++) {
			$j = 0;

			for (; $j < $cellNum; $j++) {
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
			}
		}

		ob_end_clean();
		header('pragma:public');
		header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
		header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->update('php://output');
		exit();
	}

	public function importExecl($file)
	{
		die();
		import('Org.Util.PHPExcel');
		import('Org.Util.PHPExcel.Writer.Excel5');
		import('Org.Util.PHPExcel.IOFactory.php');

		if (!file_exists($file)) {
			return array('error' => 0, 'message' => 'file not found!');
		}

		$objReader = PHPExcel_IOFactory::createReader('Excel5');

		try {
			$PHPReader = $objReader->load($file);
		}
		catch (Exception $e) {
            exception_log($e,__FUNCTION__);
		}

		if (!file_exists($file)) {
			return array('error' => 0, 'message' => 'read error!');
		}

		$allWorksheets = $PHPReader->getAllSheets();
		$i = 0;

		foreach ($allWorksheets as $objWorksheet) {
			$sheetname = $objWorksheet->getTitle();
			$allRow = $objWorksheet->getHighestRow();
			$highestColumn = $objWorksheet->getHighestColumn();
			$allColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
			$array[$i]['Title'] = $sheetname;
			$array[$i]['Cols'] = $allColumn;
			$array[$i]['Rows'] = $allRow;
			$arr = array();
			$isMergeCell = array();

			foreach ($objWorksheet->getMergeCells() as $cells) {
				foreach (PHPExcel_Cell::extractAllCellReferencesInRange($cells) as $cellReference) {
					$isMergeCell[$cellReference] = true;
				}
			}

			$currentRow = 1;

			for (; $currentRow <= $allRow; $currentRow++) {
				$row = array();
				$currentColumn = 0;

				for (; $currentColumn < $allColumn; $currentColumn++) {
					$cell = $objWorksheet->getCellByColumnAndRow($currentColumn, $currentRow);
					$afCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn + 1);
					$bfCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn - 1);
					$col = PHPExcel_Cell::stringFromColumnIndex($currentColumn);
					$address = $col . $currentRow;
					$value = $objWorksheet->getCell($address)->getValue();

					if (substr($value, 0, 1) == '=') {
						return array('error' => 0, 'message' => 'can not use the formula!');
						exit();
					}

					if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
						$cellstyleformat = $cell->getParent()->getStyle($cell->getCoordinate())->getNumberFormat();
						$formatcode = $cellstyleformat->getFormatCode();

						if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
							$value = gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($value));
						} else {
							$value = PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
						}
					}

					if ($isMergeCell[$col . $currentRow] && $isMergeCell[$afCol . $currentRow] && !empty($value)) {
						$temp = $value;
					} else if ($isMergeCell[$col . $currentRow] && $isMergeCell[$col . ($currentRow - 1)] && empty($value)) {
						$value = $arr[$currentRow - 1][$currentColumn];
					} else if ($isMergeCell[$col . $currentRow] && $isMergeCell[$bfCol . $currentRow] && empty($value)) {
						$value = '';
					}

					$row[$currentColumn] = $value;
				}

				$arr[$currentRow] = $row;
			}

			$array[$i]['Content'] = $arr;
			$i++;
		}

		spl_autoload_register(array('Think', 'autoload'));
		unset($objWorksheet);
		unset($PHPReader);
		unset($PHPExcel);
		unlink($file);
		return array('error' => 1, 'data' => $array);
	}

	public function xiazai()
	{
		
		
		die();	
		if (!check($_GET['file'], 'dw', '-.')) {
			$this->error('失败！');
		}

        DownloadFile(DATABASE_PATH . $_GET['file']);
        exit();
    }

    //以太系列归集
    public function guiji()
    {
        $coin = trim(input('coinname/s'));//$_POST['coinname'];
        $fee = trim(input('fee/f', 0.006));//$_POST['fee'];
        $num = 0;
        if (IS_POST) {
            if ($coin) {
                $coinconf = Db::name('Coin')->where(['name' => $coin, 'status' => 1])->find();
                if (!$coinconf) {
                    $this->error('无效的币种!');
                }
                $dj_username = $coinconf['dj_yh'];
                $dj_address = $coinconf['dj_zj'];
                $dj_port = $coinconf['dj_dk'];
                $CoinClient = EthClient($dj_address, $dj_port);
                $json = $CoinClient->eth_blockNumber(true);

                if (empty($json) || $json <= 0) {
                    $this->error('钱包连接失败');
                }
                if ($coinconf['type'] == 'eth' || $coinconf['type'] == 'token') {
                    //筛选数据库中钱包中大于 0.01 的用户 分组
                    $offset = 50;
                    $coinb = $coin.'b';
                    $coinp = $coin.'p';
                    $coin_sum = 0;
                    $id = 0;
                    $accounts = Db::name('UserCoin')->where([$coinb => ['neq', ''], $coinp => ['neq', '']])->select();
                    //筛选钱包中钱包中大于 0.01 的用户
                    if (count($accounts) > 0) {
                        foreach ($accounts as $k => $v) {
                            if ($coinconf['type'] == 'eth') {
                                $num = $CoinClient->eth_getBalance($v[$coinb]);
                            } elseif ($coinconf['type'] == 'token') {
                                $call = [
                                    'to' => $coinconf['token_address'],
                                    'data' => '0x70a08231' . $CoinClient->data_pj($v[$coinb])
                                ];
                                $num = $CoinClient->real_banlance_token($CoinClient->decode_hex($CoinClient->eth_call($call)), $coinconf['decimals']);
                            }
                            if ($num > 0.01) {
                                //转币小脚本 进行同步
                                do {
                                    if ($coinconf['type'] == 'eth') {
                                        $num = $num - $fee;
                                        $tradeInfo = [[
                                            'from' => $v[$coinb],
                                            'to' => $dj_username,
                                            'gas' => '0x1046a',
                                            'value' => $CoinClient->encode_dec($CoinClient->to_real_value(floatval($num))),
                                            'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                                        ]];
                                        $sendrs = $CoinClient->eth_sendTransaction($v[$coinb], $v[$coinp], $tradeInfo);
                                        $err = $sendrs->error;
                                        if ($err) {
                                            echo '<pre>';
                                            echo $id . '  地址:' . $v[$coin .'b'] . '  错误信息' . $sendrs->error->message . '..........归集出错';exit;
                                            echo '</pre>';
                                        } else {
                                            $coin_sum += $num;
                                            $id = ++$id;
                                            echo '<pre>';
                                            echo $id . '归集币种:' . $coin . '  地址:' . $v[$coin . 'b'] . '   数量:' . $num . '  交易哈希:' . $sendrs->result . '......已归集';
                                            echo '</pre>';
                                        }
                                    } elseif ($coinconf['type'] == 'token') {
                                        $value = $CoinClient->encode_dec($CoinClient->to_real_value_token(floatval($num), $coinconf['decimals']));
                                        $tradeInfo = [[
                                            'from' => $v[$coin . 'b'],
                                            'to' => $coinconf['token_address'],
//                                            'gas' => '0x1046a',
                                            'data' => '0xa9059cbb' . $CoinClient->data_pj($dj_username, $value),
//                                           'gasPrice' => '0x189640200', //$CoinClient->eth_gasPrice()
                                        ]];
                                        $sendrs = $CoinClient->eth_sendTransaction($v[$coinb], $v[$coinp], $tradeInfo);
                                        $err = $sendrs->error;
                                        if ($err) {
                                            echo '<pre>';
                                            echo $id . '  地址:' . $v[$coin . 'b'] . '  错误信息' . $sendrs->error->message . '..........归集出错';exit;
                                            echo '</pre>';
                                        } else {
                                            $coin_sum += $num;
                                            $id = ++$id;
                                            echo '<pre>';
                                            echo $id . ' 归集币种:' . $coin . '  地址:' . $v[$coin . 'b'] . '   数量:' . $num . '  交易哈希:' . $sendrs->result . '......已归集';
                                            echo '</pre>';
                                        }
                                    }
                                } while ($sendrs->error != '');
                            }
                        }
                    }
                    if ($coin_sum) {
                        echo '轮询完毕请在币种设置查看归集数量' . '  归集总数:' . $coin_sum;
                        exit;
                    } else {
                        $this->error('当前没有可归集数量!');
                    }

                } else {
                    $this->error('币种类型不符!');
                }
            } else {
                $this->error('无效的参数!!');
            }
        }
        return $this->fetch();
    }


	public function queue()
	{
		$file_path = DATABASE_PATH . '/check_queue.json';
		$timeArr = [];

		if (file_exists($file_path)) {
			$timeArr = file_get_contents($file_path);
			$timeArr = json_decode($timeArr, true);
		}

		$str = '';
		foreach ($timeArr as $key => $val) {
			if ($key == 0) {
				$val = '上一次执行:' . addtime($val);
			}

			if ($key == 1) {
				$val = '上二次执行:' . addtime($val);
			}

			if ($key == 2) {
				$val = '上三次执行:' . addtime($val);
			}

			$str .= $val . ' ';
		}

		$count = count($timeArr);
		if (3 <= $count) {
			$_t1 = $timeArr[2] - $timeArr[1];
			$_t2 = $timeArr[1] - $timeArr[0];

			if (60 < abs($timeArr[0] - time())) {
				$status = '<span class="btn btn-warning">队列停止运行</span>';
			}

			if ((50 < abs($_t1)) && (50 < abs($_t2))) {
				$status = '<span class="btn">队列运行正常</span>';
			} else {
				//$status = '<span class="btn btn-warning">队列时间异常,请稍后再试</span>';
				$status = '<span class="btn">队列运行正常</span>';
			}
		} else {
			$msg = '';

			if ($count == 0) {
				$msg = '队列还未开始运行,请1分钟后刷新';
			}

			if ($count == 1) {
				$msg = '队列运行一次请再等待2分钟检查';
			}

			if ($count == 2) {
				$msg = '队列运行两次请再等待1分钟检查';
			}

			$status = '<span class="btn btn-warning">' . $msg . '</span>';
		}

		$this->assign('status', $status);
		$this->assign('str', $str);
		return $this->fetch();
	}

	public function qianbao()
	{
        $id= input('id/d');
		$s_key = md5('ToolsQianbao');
		$qb_list = cache($s_key);

		if (!$qb_list) {
			$qb_list = Db::name('Coin')->where(['type' => ['in', 'bit,eth']])->select();
			cache($s_key, $qb_list);
		}

		if (empty($id)) {
			cache($s_key, null);
			$this->assign('list_len', count($qb_list));
			return $this->fetch();
			exit();
		}

		if ($id == -1) {
			$dirs = array('./../runtime/');
			@(mkdir('runtime', 511, true));

			foreach ($dirs as $value) {
				$this->rmdirr($value);
			}

			@(mkdir('runtime', 511, true));
			echo json_encode(array('status' => 1, 'info' => '缓存清除成功'));
			exit();
		}

		$update_str = '&nbsp;&nbsp;&nbsp;<a href="' . url('Coin/edit', array('id' => $qb_list[$id]['id'])) . '" color="green" target="_black">立即前往修改<a>';

		if (isset($qb_list[$id])) {
			if ($qb_list[$id]['status']) {
				if ($qb_list[$id]['zr_dz'] <= 0) {
					echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . '钱包确认次数不能为空' . $update_str));
					exit();
				}

				if ($qb_list[$id]['zc_zd'] <= 10) {
					echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . '钱包自动转出限额太小,建议10个以上' . $update_str));
					exit();
				}
					
				//zj  IP  Dk  端口    yh 用户  mm 密码	
					
				$CoinClient = CoinClient($qb_list[$id]['dj_yh'], $qb_list[$id]['dj_mm'], $qb_list[$id]['dj_zj'], $qb_list[$id]['dj_dk'], 3, array(), 1);
				$json = $CoinClient->getinfo();

				if ($json) {
					if ($tmp = json_decode($json, true)) {
						$json = $tmp;
					}
				}

				if (!isset($json['version']) || !$json['version']) {
					echo json_encode(array('status' => -2, 'info' => $qb_list[$id]['title'] . '服务器返回错误:' . $json['data'] . $update_str));
					exit();
				} else {
					echo json_encode(array('status' => 1, 'info' => $qb_list[$id]['title'] . '运行正常'));
					exit();
				}
			} else {
				echo json_encode(array('status' => -1, 'info' => $qb_list[$id]['title'] . '已经禁用,不用检查'));
			}
		} else {
			echo json_encode(array('status' => 100, 'info' => '全部检查完毕'));
			exit();
		}
	}

	public function jiancha()
	{
        $id = input('id/d');
		if ($id === null) {
			return $this->fetch();
			exit();
		}

		if ($id == -1) {
			$dirs = array('./../runtime/');
			@(mkdir('runtime', 511, true));

			foreach ($dirs as $value) {
				
			}

			@(mkdir('runtime', 511, true));
			echo json_encode(array('status' => 1, 'info' => '缓存清除成功'));
			exit();
		}

		if ((0 <= $id) && ($id <= 19)) {
			$dirfile = check_dirfile();
			echo json_encode(array('status' => $dirfile[$id][2] == 'ok' ? 1 : -2, 'info' => $dirfile[$id][3] . $dirfile[$id][1]));
			exit();
		}

		if (19 < $id) {
			echo json_encode(array('status' => 100, 'info' => '检查完成'));
			exit();
		}
	}
}

?>
        
        