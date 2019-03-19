<?php
namespace app\admin\controller;

class File extends Admin
{
	public function upload()
	{
		$return = array('status' => 1, 'info' => '上传成功', 'data' => '');
		$File = model('File');
		$file_driver = config('DOWNLOAD_UPLOAD_DRIVER');
		$info = $File->upload($_FILES, config('DOWNLOAD_UPLOAD'), config('DOWNLOAD_UPLOAD_DRIVER'), config('UPLOAD_' . $file_driver . '_CONFIG'));

		if ($info) {
			$return['data'] = json_encode($info['download']);
			$return['info'] = $info['download']['name'];
		}
		else {
			$return['status'] = 0;
			$return['info'] = $File->getError();
		}

		$this->result($return);
	}

	public function download()
	{
        $id = input('param.id/d');
		if (empty($id) || !is_numeric($id)) {
			$this->error('参数错误！');
		}

		$logic = model('Download', 'Logic');

		if (!$logic->download($id)) {
			$this->error($logic->getError());
		}
	}

	public function uploadPicture()
	{
		$return = array('status' => 1, 'info' => '上传成功', 'data' => '');
		$Picture = model('Picture');
		$pic_driver = config('PICTURE_UPLOAD_DRIVER');
		$info = $Picture->upload($_FILES, config('PICTURE_UPLOAD'), config('PICTURE_UPLOAD_DRIVER'), config('UPLOAD_' . $pic_driver . '_CONFIG'));

		if ($info) {
			$return['status'] = 1;
			$return = array_merge($info['download'], $return);
		}
		else {
			$return['status'] = 0;
			$return['info'] = $Picture->getError();
		}

		$this->result($return);
	}
}

?>