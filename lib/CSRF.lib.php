<?php defined('FLOO') or die('potato'); // NOOOO

/**
 * CSRF令牌类，其主要目的是为了检查提交表单的来源是否是同一站点，以避免跨站点请求伪造攻击（CSRF）。
 * CSRF token class
 * Project name: Flatboard
 * Project URL: https://flatboard.org
 * Author: Frédéric Kaplon and contributors
 * All Flatboard code is released under the MIT license.
 */
class CSRF
{
    /**
     * Protected constructor since this is a static class.
     *
     * @access  protected
     */
    protected function __construct()
    {
        // Nothing here
    }
    /**
     * 基于IP地址和随机字符串生成新的CSRF令牌。 
     *
     * @return string
     */
    public static function generate()
    {	
        // Fixed by HD#20af3 https://www.flatboard.org/view.php/topic/2016-12-071103229c648
        return $_SESSION['token'] = base64_encode(implode('|', array(md5($_SERVER['REMOTE_ADDR']), uniqid())));
    }

    /**
     * 检查给定的令牌和IP地址是否有效。
     *
     * @param $token
     *
     * @return bool true为有效
     */
    public static function check($token)
    {
        if (DEBUG_MODE) {
            // 调试模式暂不需要检查CSRF
            return true;
        }
        if (isset($_SESSION['token']) && $token === $_SESSION['token']) {
            $ex = explode('|', base64_decode($token));
            if ($ex[0] !== md5($_SERVER['REMOTE_ADDR'])) {
                return false;
            }
            unset($_SESSION['token']);
            return true;
        }
		$_SESSION['ErrToken'] = 1;
        return false;
    } 
           
}