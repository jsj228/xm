<?php

class Tool_Math
{
	/**
     * 加法（高精度）
     *
     * @param $num1 加数
     * @param $num2 加数
     * @param $scale 最大小数位
     * @param $effective 是否保留有效小数
     * @param $pRule 规则 0:四舍五入, 1:全入, 2:全舍
     * @return string
     */
	static function add($num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		return self::operate('add', $num1, $num2, $scale, $pRule, $effective);
	}

	/**
     * 减法（高精度）
     *
     * @param $num1 被减数
     * @param $num2 减数
     * @param $scale 最大小数位
     * @param $effective 是否保留有效小数
     * @param $pRule 规则 0:四舍五入, 1:全入, 2:全舍
     * @return string
     */
	static function sub($num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		return self::operate('sub', $num1, $num2, $scale, $pRule, $effective);
	}


	/**
     * 乘法（高精度）
     *
     * @param $num1 加数
     * @param $num2 加数
     * @param $scale 最大小数位
     * @param $effective 是否保留有效小数
     * @param $pRule 规则 0:四舍五入, 1:全入, 2:全舍
     * @return string
     */
	static function mul($num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		return self::operate('mul', $num1, $num2, $scale, $pRule, $effective);
	}


	/**
     * 除法（高精度）
     *
     * @param $num1 加数
     * @param $num2 加数
     * @param $scale 最大小数位
     * @param $effective 是否保留有效小数
     * @param $pRule 规则 0:四舍五入, 1:全入, 2:全舍
     * @return string
     */
	static function div($num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		return self::operate('div', $num1, $num2, $scale, $pRule, $effective);  
	}


	/**
     * 计算（高精度）
     *
     * @param $type 运算类型 加法：add, 减法: sub, 乘法: mul, 除法：div
     * @param $num1 左操作数
     * @param $num2 右操作数
     * @param $scale 最大小数位
     * @param $effective 是否保留有效小数
     * @param $pRule 规则 0:四舍五入, 1:全入, 2:全舍
     * @return string
     */
	static function operate($type, $num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		$num1 = self::format($num1, $scale+1, $pRule);
		$num2 = self::format($num2, $scale+1, $pRule);

		switch ($type) 
		{
			case 'add':
				$result = bcadd($num1, $num2, $scale);
				break;
			case 'sub':
				$result = bcsub($num1, $num2, $scale);
				break;
			case 'mul':
				$result = bcmul($num1, $num2, $scale);
				break;
			case 'div':
				$result = bcdiv($num1, $num2, $scale);
				break;
			case 'comp':
				$result = bccomp($num1, $num2, $scale);
				break;	
			default:
				throw new Exception("operate type error");
		}
		if($effective)
		{
			$result = self::eftnum($result);
		}
		return $result;
	}


	/**
	 * 格式化数字
	 * @param $pNum
	 * @param int $pLen
	 * @param int $pRule 规则 0:四舍五入, 1:全入, 2:全舍
	 * @param bool $effective 规则 flase:保留指定位数小数, true:保留有效小数
	 * @return int | float
	 */
	static function format($pNum, $pLen = 20, $pRule = 0, $effective=true){
		//处理科学计数法
		if(false !== stripos($pNum, "e")){  
	        $a = explode("e",strtolower($pNum));
	        $pNum = bcmul($a[0], bcpow(10, $a[1], $pLen+1), $pLen+1);
	    } 

	    if(strpos($pNum, '.'))
	    {
			list($int, $dec) = explode('.', strval($pNum));
	    }
	    else
	    {
	    	$int = (string)$pNum;
	    }
		
		if(isset($dec))
		{
			$returnNum = trim(intval($int).'.'.substr($dec, 0, $pLen), '-');
			if( ($pRule==0 && isset($dec{$pLen}) && $dec{$pLen}>4) || ($pRule==1 && substr($dec, $pLen)>0) )
			{
				$returnNum = bcadd($returnNum, '0.'.str_pad('', $pLen-1, 0).'1', $pLen);
			}
			if($pNum<0)
			{
				$returnNum = '-'.$returnNum;
			}
		}
		else
		{
			$returnNum = $pNum;
		}
	    
		
		#保留有效小数
		if($effective)
		{
			$returnNum = self::eftnum($returnNum);
		}

		return $returnNum;
	}


	/**
	 * 保留有效小数
	 * @param $number
	 * @return str
	 */
	static function eftnum($number)
	{
		return trim(preg_replace('/(\.\d*?)0+$/', '$1', $number), '.');
	}


	/**
	 * 比较大小，相等返回0，左边大返回1， 右边大返回-1
	 * @param $number
	 * @return str
	 */
	static function comp($num1, $num2, $scale=20, $pRule=0, $effective=true)
	{
		return self::operate('comp', $num1, $num2, $scale, $pRule, $effective);  
	}

}