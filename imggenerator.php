<?php

class ImgGenerator {
	
	private $config;
	
	public function __construct(){
		$this->config = Config::getImageConfig();
	}
	
	public function generateByText($text){
		$ffile = $this->config['fontfile'];
		$fsize = $this->config['elements']['error']['fontsize'];
		
		$bounds = imagettfbbox($fsize, 0, $ffile, $text);
		$width = $bounds[4] - $bounds[6];
		$height = $bounds[3] - $bounds[5];
		$space = 5;
		$image = imagecreatetruecolor($width + $space, $height + $space);
		$colors['bg'] = imagecolorallocate($image, 255, 255, 255);
		$colors['fg'] = imagecolorallocate($image, 51, 51, 51);
		imagefilledrectangle($image, 0, 0, $width + $space, $height + $space, $colors['bg']);
		$x = $space;
		$y = $fsize + $space;
		imagettftext($image, $fsize, 0, $x, $y, $colors['fg'], $ffile, $text);
		
		$this->showImage($image);
	}
	
	public function generateSignature($info){
		$cfg = $this->config;
		$ffile = $cfg['fontfile'];
		$gsize = $cfg['avatar']['size'];

		$width = $cfg['img_width'];
		$heigth = $cfg['img_heigth'];
		
		$colors = $cfg['col'];
		
		$back_col = $colors['background'];
		
		//Define avatar and destination signature images
		$avatar = imagecreatefromjpeg($info['avatar_url']);
		$im = @imagecreatetruecolor($width, $heigth) or die ('Could not create image');
		
		//Allocate colors
		$black = imagecolorallocate($im, 0, 0, 0);
		$white = imagecolorallocate($im, 255, 255, 255);
		
		//Fill background
		$bkgcol = $colors['background'];
		$bkgcol = imagecolorallocate($im, $bkgcol[0], $bkgcol[1], $bkgcol[2]);
		imagefilledrectangle($im, 0, 0, $width, $heigth, $bkgcol);
		unset($bkgcol);
		
		//Copy avatar image into destination image
		$offsetX = + $cfg['avatar']['offsetX'];
		$offsetY = + $cfg['avatar']['offsetY'];
		imagecopy($im, $avatar, 0 + $offsetX, 0 + $offsetY, 0, 0, imagesx($avatar), imagesy($avatar));
		
		//Add username as header to image
		$usr = $cfg['elements']['username'];
		$header_y = $usr['fontsize'] + $usr['offsetY'];
		$box = imagettftext($im, $usr['fontsize'], 0, $gsize + $usr['offsetX'], $usr['fontsize'] + $usr['offsetY'], $black, $ffile, $info['username']);
		$border = $box[2];
		unset($box, $usr, $avatar);

		//Add items to image
		
		$itemcfg = $cfg['elements']['items'];
		$starcfg = $cfg['elements']['stars'];
		$rfsize = $itemcfg['fontsize'];
		$rowY[0] = $header_y + $rfsize + $itemcfg['offsetY'];
		$max_item_width = 0;
		$max_startxt_width = 0;
		
		//Add item names
		foreach($info['items'] as $num=>$item){
			//Write row positions
			if($num > 0) $rowY[$num] = $rowY[$num - 1] + $rfsize + $itemcfg['offsetY'];
			$itemname = $info['items'][$num]['name'];
			
			imagettftext($im, $rfsize, 0, $gsize + $itemcfg['offsetX'], $rowY[$num], $black, $ffile, $itemname);
			
			//Determine max width for table style indention
			$box = imagettfbbox($rfsize, 0, $ffile, $itemname);
			if($box[4] > $max_item_width) $max_item_width = $box[4];
		}
		
		//Add item star counts
		foreach($info['items'] as $num=>$item){
			//Add item star count
			$stars = $info['items'][$num]['stars'];
			imagettftext($im, $rfsize, 0, $gsize + $max_item_width + $starcfg['text_offsetX'], $rowY[$num], $black, $ffile, $stars);
			
			//Determine max width for table style indention
			$box = imagettfbbox($rfsize, 0, $ffile, $stars);
			if($box[4] > $max_startxt_width) $max_startxt_width = $box[4];
		}
		
		//Add item star images
		$stars_cfg = $this->config['elements']['stars'];
		$star_im = imagecreatefrompng($stars_cfg['img_file']);
		$start_x = $gsize + $max_item_width + $starcfg['text_offsetX'] + $max_startxt_width + $stars_cfg['img_offsetX'];

		foreach($info['items'] as $num=>$item){
			imagecopy($im, $star_im, $start_x, $rowY[$num] + $stars_cfg['img_offsetY'], 0, 0, imagesx($star_im), imagesy($star_im));
		}
		
		$end_x = $start_x + imagesx($star_im);
		if($end_x > $border)
			$border = $end_x;
		//TODO: Crop image if needed
		
		unset($itemcfg, $rfsize, $rowY, $stars_cfg, $star_im, $start_x);
		
		//Pass complete image
		$this->showImage($im);
	}
	
	private function showImage($image){
		if(!isset($_GET['raw'])) header('Content-type: image/png');
		imagepng($image);
		imagedestroy($image);
		exit();
	}
	
}
