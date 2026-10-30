<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * 验证码【吸纳自 WebPress/libs/Captcha.lib.php（Captcha）】
 *
 * 适配点（相对源）：
 * - 守卫 WEBPRESS → FLOO
 * - 图片 src 指向 lib/captcha/img.php（floobb 落点），不再相对 WebPress 目录
 * - createCaptcha() 去掉 FontAwesome 依赖：刷新按钮改用文字「↻」+ aria-label（a11y）
 * - 保留 Bootstrap 5 input-group markup（默认主题即 Bootstrap 5）
 * - checkCaptcha() 逻辑不变：比对 $_SESSION['captcha_code']
 */
class Captcha
{
	/**
	 * 输出验证码输入框 HTML（含图片与刷新按钮）
	 */
	public static function createCaptcha($w=200, $h=38, $bgr=128, $bgg=128, $bgb=128, $cr=0, $cg=0, $cb=0, $size=20, $angle=0){
		$src = BASEPATH . DS . 'lib' . DS . 'captcha' . DS . 'img.php'
			. '?captcha=w-' . (int)$w . '+h-' . (int)$h
			. '+bgr-' . (int)$bgr . '+bgg-' . (int)$bgg . '+bgb-' . (int)$bgb
			. '+cr-' . (int)$cr . '+cg-' . (int)$cg . '+cb-' . (int)$cb
			. '+size-' . (int)$size . '+angle-' . (int)$angle;
		$out = '<div class="input-group mb-3">
	<label class="input-group-text" for="captcha">Captcha</label>
	<input class="form-control" type="text" maxlength="6" id="captcha" name="captcha" required="true" autocomplete="off"/>
	<span class="input-group-text"><img src="' . $src . '" id="captchaImg" alt="captcha" width="' . (int)$w . '" height="' . (int)$h . '"/><button type="button" class="btn btn-secondary ms-1 captcha-reload" aria-label="reload captcha">&#8635;</button></span>
</div>';
		$out .= '<script>
			document.querySelector(".captcha-reload").addEventListener("click", function(){
				let img = document.querySelector("#captchaImg");
				img.src = img.src.split("&r=")[0] + "&r=" + Math.random();
			});
		</script>';
		return $out;
	}

	/**
	 * 校验用户输入的验证码
	 */
	public static function checkCaptcha($code){
		return isset($_SESSION['captcha_code']) && $code === $_SESSION['captcha_code'];
	}
}
