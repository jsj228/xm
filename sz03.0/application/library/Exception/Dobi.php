<?php
/**
 * DOBI 通用输出异常
 */
class DobiException extends Exception
{
	protected $data;

	function __construct($msg, $code=0, $data='')
	{
		$this->data = $data;
		parent::__construct($msg, $code);
	}

	public function getData()
	{
		return $this->data;
	}
}