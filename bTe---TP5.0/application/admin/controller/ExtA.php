<?php
namespace Admin\Controller;

class ExtAController extends AdminController
{
	public function index()
	{
		redirect(__MODULE__.'/Cloud/update');
		$this->display();
	}
}

?>