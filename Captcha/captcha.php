<?php
/**index.php
 * displays a captcha to the user and stores the correct answer in the a session variable
 * stores all captchas in a file: capppy.dat; with the format *captcha\ncaptcha\n"
 **/

session_start();

$captcha_used = file("cappy.dat", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);//holds all captcha's that have been used

class CaptchaSecurityImages {

   var $font = 'Dekka Dense JL.ttf';

   function getCaptcha($length)
	{
		$alphabete = "bcdfghjklmnpqrstwxyz23456789";//28 options
		//$length = 6;//This gives 481890304 options
		//$length = 7;//this gives 13492928512 options (13011038208 difference from 6)

		return substr(str_shuffle($alphabete), 0, $length);
	}

   function CaptchaSecurityImages($width='120',$height='40',$length=7)
	{
		global $captcha_used;
		$code = $this->getCaptcha($length);
		while(true)
		{
			if(array_search($code, $captcha_used) === false)//unique captcha
			{
				break;
			}
			else
			{
				$code = $this->getCaptcha($length);
			}
		}

		/* font size will be 75% of the image height */
		$font_size = $height * 0.42;
		$image = imagecreate($width, $height) or die('Cannot initialize new GD image stream');
		/* set the colours */
		$background_color = imagecolorallocate($image, 255, 255, 255);
		$text_color = imagecolorallocate($image, 80, 40, 100);
		$noise_color = imagecolorallocate($image, 10, 200, 180);
		/* generate random dots in background */
		for( $i=0; $i<($width*$height)/3; $i++ ) {
		 imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
		}
		/* generate random lines in background */
		for( $i=0; $i<($width*$height)/150; $i++ ) {
		 imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
		}

		/* create textbox and add text */
		$textbox = imagettfbbox($font_size, 0, $this->font, $code) or die('Error in imagettfbbox function');
		$x = ($width - $textbox[4])/2;
		$y = ($height - $textbox[5])/2;
		imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font , $code) or die('Error in imagettftext function');
		/* output captcha image to browser */
		header('Content-Type: image/jpeg');
		imagejpeg($image);
		imagedestroy($image);
		$_SESSION['security_code'] = $code;
   }

}

$width = isset($_GET['width']) && $_GET['width'] < 600 ? $_GET['width'] : '120';
$height = isset($_GET['height']) && $_GET['height'] < 200 ? $_GET['height'] : '40';
$characters = isset($_GET['characters']) && $_GET['characters'] > 2 ? $_GET['characters'] : 7;

$captcha = new CaptchaSecurityImages($width,$height,$characters);

?>