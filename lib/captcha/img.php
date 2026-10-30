<?php
/**
 * 直访脚本：不经 index.php（.htaccess !-f 放行），自带 FLOO 常量
 */
define('FLOO', TRUE);
defined('FLOO') or die('potato'); // NOOOO

/**
 * 验证码图片【吸纳自 WebPress/libs/captcha/img.php，适配 floobb】
 *
 * 适配点（相对源）：
 * - 加 FLOO 守卫；由 lib/Captcha.lib.php 配套引用
 * - 字体路径去 WebPress 化：优先 Windows 系统字体（arial.ttf / simhei.ttf），找不到则回退 GD 内置字体
 * - 修 bug：header 声明 image/jpeg 但实际 imagepng 输出 → 统一 image/png
 * - md5(rand()) → random_bytes（CSPRNG）
 * - 参数解析收紧：逐项 intval + 范围钳制
 *
 * 可直接被浏览器访问（.htaccess 保留 !-f 条件放行静态命中）：
 *   http://127.0.0.1/floobb/lib/captcha/img.php?captcha=w-200+h-38+...
 */

function floob_captcha_font() {
	$candidates = array(
		'C:\\Windows\\Fonts\\arial.ttf',
		'C:\\Windows\\Fonts\\simhei.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
	);
	foreach ($candidates as $f) {
		if (is_file($f)) return $f;
	}
	return '';
}

function floob_create_captcha($w=200, $h=38, $bgr=128, $bgg=128, $bgb=128, $cr=0, $cg=0, $cb=0, $size=20, $angle=0){
	if (session_status() !== PHP_SESSION_ACTIVE) session_start();

	$captcha_code = substr(bin2hex(random_bytes(6)), 0, 6);

	$_SESSION['captcha_code'] = $captcha_code;

	header('Content-Type: image/png');
	header('Cache-Control: no-store, no-cache, must-revalidate');

	$image = imagecreatetruecolor($w, $h);

	$background_color = imagecolorallocate($image, $bgr, $bgg, $bgb);
	$text_color = imagecolorallocate($image, $cr, $cg, $cb);

	imagefilledrectangle($image, 0, 0, $w, $h, $background_color);

	// 干扰点
	for ($i = 0; $i < 50; $i++) {
		$noise = imagecolorallocate($image, random_int(100, 200), random_int(100, 200), random_int(100, 200));
		imagesetpixel($image, random_int(0, $w - 1), random_int(0, $h - 1), $noise);
	}

	$font = floob_captcha_font();
	if ($font !== '' && function_exists('imagettftext')) {
		imagettftext($image, $size, $angle, (int)($w / 2) - 50, (int)($h / 2) + ($size / 2) - 2, $text_color, $font, $captcha_code);
	} else {
		// 回退：GD 内置字体
		imagestring($image, 5, (int)($w / 2) - 50, (int)($h / 2) - 8, $captcha_code, $text_color);
	}

	imagepng($image);
	imagedestroy($image);
}

if(isset($_GET['captcha'])){
	$plus = str_replace(' ','+', $_GET['captcha']);
	$raw = explode('+', $plus);
	$attr = array();
	// 每个 token 形如 "w-200"：取第一个 '-' 之后的数值
	foreach ($raw as $piece) {
		$pos = strpos($piece, '-');
		$attr[] = ($pos === false) ? 0 : (int) substr($piece, $pos + 1);
	}
	floob_create_captcha(
		max(50, min($attr[0] ?? 200, 600)),   // w
		max(24, min($attr[1] ?? 38, 120)),    // h
		$attr[2] ?? 128, $attr[3] ?? 128, $attr[4] ?? 128,
		$attr[5] ?? 0, $attr[6] ?? 0, $attr[7] ?? 0,
		max(10, min($attr[8] ?? 20, 48)),     // size
		0                                      // angle 固定 0，避免参数注入
	);
}
