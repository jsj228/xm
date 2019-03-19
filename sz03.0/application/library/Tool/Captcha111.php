<?php
class Tool_Captcha{
	private $width;
	private $height;
	private $codeNum;
	private $code;
	private $im;
	private $serverSign;

	private static $instance;

	function __construct($width = 80, $height = 20, $codeNum = 4){

		if(!isset($_SESSION))
			throw new Exception("session not started");

		$this->width = $width;
		$this->height = $height;
		$this->codeNum = $codeNum;
		$this->serverSign = isset($_SERVER['HTTP_TOKEN'])?trim($_SERVER['HTTP_TOKEN']):$_COOKIE[SESSION_NAME];

		
	}

	function showImg(){
		# 创建图片
		$this->createImg();
		# 设置干扰元素
		$this->setDisturb();
		# 设置验证码
		$this->setCaptcha();
		# 输出图片
		$this->outputImg();
	}

	function getCaptcha(){
		return $this->code;
	}

	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function check($captcha){
		Cache_Redis::instance()->select(0);
		$this->code = Cache_Redis::instance()->get($this->serverSign);
		if(strlen($captcha) == $this->codeNum)// && strtolower($captcha) === strtolower($this->code)
		{
			//Cache_Redis::instance()->del($_COOKIE[SESSION_NAME]);
			return true;
		}
		else
		{
			$this->code = null;
			$this->delCaptcha();
			return false;
		}
	}

	public function delCaptcha()
	{
		$this->code = null;
		Cache_Redis::instance()->del($this->serverSign);
	}

	private function createImg(){
		$this->im = imagecreatetruecolor($this->width, $this->height);
		$bgColor = imagecolorallocate($this->im, 235,244,254);
		imagefill($this->im, 0, 0, $bgColor);
	}

	private function setDisturb(){
		$area = ($this->width * $this->height) / 20;
		$disturbNum = ($area > 250)? 50: $area;
		# 加入点干扰
		for($i = 0; $i < $disturbNum; $i++){
			$color = imagecolorallocate($this->im, rand(60, 66), rand(110, 118), rand(20, 28));
			imagesetpixel($this->im, rand(1, $this->width - 2), rand(1, $this->height - 2), $color);
		}
		# 加入弧线
		$color = imagecolorallocate($this->im, rand(60, 66), rand(110, 118), rand(20, 28));
		imagearc($this->im, rand(0, $this->width), rand(0, floor($this->height/2)), $this->width, floor($this->height/2), rand(0, 20), rand(130, 180), $color);

		// imagearc($this->im, rand(0, floor($this->width/1.5)), floor($this->height/2), rand(floor($this->width/2), floor($this->width/5)), rand($this->height*2, $this->height/3), rand(50, 90), rand(200, 250), $color);

	}

	private function createCode(){
		$str = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKMNPQRSTUVWXYZ";
		$this->code = '';
		for($i = 0; $i < $this->codeNum; $i++){
			$this->code .= $str{rand(0, strlen($str) - 1)};
		}
		Cache_Redis::instance()->set($this->serverSign, $this->code, 300);
	}

	private function setCaptcha(){
		$this->createCode();
		$closeNum = ['min'=>1, 'max'=>2];//相連字符數範圍
		$currentCloseNum = 0;//相連字符數
		for($i = 0; $i < $this->codeNum; $i++){
			$color = imagecolorallocate($this->im, rand(60, 66), rand(110, 118), rand(20, 28));
			$size = rand(floor($this->height / 5), floor($this->height / 3));
			$x = floor($this->width / $this->codeNum) * $i + rand(-1, 10);
			$y = rand(0, $this->height - 20);
			if($i>0 && $currentCloseNum<$closeNum['max'])
			{
				if(isset($prevRx) && $prevRx)
				{
					$isClose = $currentCloseNum<$closeNum['min']?1:rand(0,1);
					$x = [$x, $prevRx - 2][$isClose];
					$isClose and $currentCloseNum++;
				}
			}
			$prevRx = $x + $size;
			imagechar($this->im, $size, $x, $y, $this->code{$i}, $color);

			$dst = imagecreatetruecolor($this->width, $this->height);  
	        $dWhite = imagecolorallocate($dst, 255, 255, 255);  
	        imagefill($dst,0,0,$dWhite);
	        
	        
		}

		//扭曲，变形
        // for($i = 0; $i < $this->width; $i++) 
        // {  
        //     // 根据正弦曲线计算上下波动的posY  
             
        //     $offset = 4; // 最大波动几个像素  
        //     $round = 2; // 扭2个周期,即4PI  
        //     $posY = round(sin($i * $round * 2 * M_PI / $this->width ) * $offset); // 根据正弦曲线,计算偏移量  
  
        //     imagecopy($dst, $this->im, $i, $posY, $i, 0, 1, $this->height);
        // } 
        
        //$this->im = $dst;
	}

	private function outputImg(){
		if(imagetypes() & IMG_PNG){
			header('Content-type:image/png');
			imagepng($this->im);
		}
		elseif(imagetypes() & IMG_GIF){
			header('Content-type: image/gif');
			imagegif($this->im);
		}
		elseif(imagetype() & IMG_JPG){
			header('Content-type: image/jpeg');
			imagejpeg($this->im);
		}
		else{
			die("Don't support image type!");
		}
	}
}
