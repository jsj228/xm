<?php
class CodeModel extends Orm_Base
{
	public $table = 'code';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'code' => array('type' => "int(6) unsigned", 'comment' => ''),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'message' => array('type' => "varchar(255) unsigned", 'comment' => ''),
		'phone' => array('type' => "char", 'comment' => ''),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => ''),
		'ctime' => array('type' => "int(11) unsigned", 'comment' => ''),
		'utime' => array('type' => "int(11) unsigned", 'comment' => '')
	);
	public $pk = 'id';

	public function sendCode($phone, $voice = 0)
	{
		$code = rand(100000, 999999);
		$message = '【元宝币】您的验证码是'.$code.'，如非本人操作，请忽略本短信。';
		$data = array(
			'code' => $code,
			'phone' => $phone,
			'status' => 0,
			'message' => $message,
			'ctime' => time()
		);

		# update before use message
		$this->where("phone = {$phone}")->update(array('status' => 2));

		if ($this->insert($data)) {
			#return true;
			if ($voice != 0) {
				$return = Tool_Message::run('voice_send', $phone , $code);
			} else {
				$return = Tool_Message::run('send', $phone, $data['message']);
			}
			return $return;
		}

		return false;
	}

	public function verify($phone, $code)
	{
		$message_id = $this->where("phone = {$phone} and code = {$code} and status = 0")->fOne('id');
		if (!$message_id) {
			return false;
		}

		if (!$this->where("id = {$message_id}")->update(array('status' => 1, 'utime' => time()))) {
			return false;
		}
		return true;
	}

}
