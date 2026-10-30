<?php

defined('FLOO') or die('FlooBB');

/**
 * 用户控制器（SPEC §3.1 路由表）
 * - view     GET/POST /user/view/{name}  个人资料页（查看他人 + 近期发帖 + Bio/头像）
 * - settings GET  /user/settings         设置表单（昵称/邮箱/Bio/头像/改密）
 * - settingsPost POST /user/settings     保存设置（CSRF；改密需验旧密码，不踢当前 session）
 * 三位置契约：/user/view/Geticer → view('Geticer')，用户名走 action 之后
 */
class UserController {

	public function index() {
		self::notImplemented('user/index：用户列表，Phase 1 实现');
	}

	public function view($name = '') {
		global $conf, $header;

		$user = User::get($name);
		if ($user === null) {
			http_response_code(404);
			$header['title'] = '用户不存在';
			require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
			echo '<div class="alert alert-danger" role="alert">用户不存在。</div>';
			echo '<a class="btn btn-secondary" href="' . Router::url() . '">返回首页</a>';
			require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
			exit;
		}

		$header['title'] = (string) ($user['nickname'] ?? $user['username']);
		$username = (string) $user['username'];
		$stats = array(
			'topics' => Topic::countByAuthor($username),
			'posts' => Post::countByAuthor($username),
		);

		// 近期发帖：主题 + 回帖（各取 5，created 降序）
		$recentTopics = Topic::listByAuthor($username, 5);
		$recentPosts = array();
		foreach (Post::listByAuthor($username, 5) as $rp) {
			$rt = Topic::get((string) ($rp['topic_id'] ?? ''));
			if ($rt === null) continue; // 主题已删的孤儿帖不展示
			$recentPosts[] = array(
				'post' => $rp,
				'topic_title' => (string) ($rt['title'] ?? ''),
				'url' => Router::url('topic/view/' . rawurlencode((string) ($rp['topic_id'] ?? '')))
					. '#p' . rawurlencode((string) ($rp['id'] ?? '')),
			);
		}

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'user_view.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
		exit;
	}

	/* ---------------- 个人设置 ---------------- */

	public function settings() {
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		self::render('user_settings', array(
			'user' => $user,
			'saved' => Router::query('saved', '') === '1',
			'errors' => array(),
			'values' => array(
				'nickname' => (string) ($user['nickname'] ?? $user['username']),
				'email' => (string) ($user['email'] ?? ''),
				'bio' => (string) ($user['bio'] ?? ''),
			),
		));
	}

	public function settingsPost() {
		if (!Auth::csrfCheck()) self::forbidden();
		$user = Auth::user();
		if ($user === null) self::redirect('auth/login');

		$nickname = trim(strip_tags((string) param('nickname', '', FALSE)));
		$email = trim((string) param('email', '', FALSE));
		$bio = trim(strip_tags((string) param('bio', '', FALSE)));
		// 密码字段禁 htmlspecialchars（会改坏原值，批次 1 教训）
		$oldPass = (string) param('old_password', '', FALSE);
		$newPass = (string) param('new_password', '', FALSE);
		$confirm = (string) param('confirm_password', '', FALSE);

		$errors = array();
		if (!User::validNickname($nickname)) $errors[] = '昵称需为 1-30 个字符，且不含控制字符。';
		if ($email !== '' && !User::validEmail($email)) $errors[] = '邮箱格式不正确。';
		if (!User::validBio($bio)) $errors[] = '一句话介绍不能超过 200 字符，且为单行文本。';

		// 改密：三项任一填写即视为请求改密，三项必须齐 + 旧密码验证
		$changePassword = ($oldPass !== '' || $newPass !== '' || $confirm !== '');
		if ($changePassword) {
			if (!password_verify($oldPass, (string) ($user['password_hash'] ?? ''))) $errors[] = '当前密码不正确。';
			if (!User::validPassword($newPass)) $errors[] = '新密码至少 8 位，且需同时包含字母与数字。';
			if ($newPass !== $confirm) $errors[] = '两次输入的新密码不一致。';
		}

		// 头像上传（Files::upload：图片白名单 + 2MB + 重命名为用户名）
		$avatarDir = UPLOADS_DIR . 'avatars';
		$hasUpload = isset($_FILES['avatar']) && is_array($_FILES['avatar'])
			&& $_FILES['avatar']['error'] === UPLOAD_ERR_OK && (string) $_FILES['avatar']['name'] !== '';
		$uploadedName = '';
		if ($hasUpload) {
			$ext = strtolower(pathinfo((string) $_FILES['avatar']['name'], PATHINFO_EXTENSION));
			$upErr = '';
			if (!Files::upload('avatar', $avatarDir, $user['username'], null, 0, $upErr)) {
				$errors[] = '头像上传失败：' . $upErr;
			} else {
				$uploadedName = $user['username'] . '.' . $ext;
			}
		}

		if (!empty($errors)) {
			self::render('user_settings', array(
				'user' => $user,
				'saved' => false,
				'errors' => $errors,
				'values' => array('nickname' => $nickname, 'email' => $email, 'bio' => $bio),
			));
		}

		// 校验全过：保存资料
		$user['nickname'] = $nickname;
		$user['email'] = $email;
		$user['bio'] = $bio;
		if ($uploadedName !== '') {
			// 换头像时清理旧文件（扩展名不同会残留）
			if (!empty($user['avatar']) && (string) $user['avatar'] !== $uploadedName) {
				Files::remove(basename((string) $user['avatar']), $avatarDir);
			}
			$user['avatar'] = $uploadedName; // 只存文件名，URL 由模板拼 uploads/avatars/
		}
		if (param('remove_avatar', '') !== '') {
			if (!empty($user['avatar'])) Files::remove(basename((string) $user['avatar']), $avatarDir);
			$user['avatar'] = '';
		}
		if ($changePassword) $user['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
		User::save($user['username'], $user);

		// 改密后作废 RememberMe 令牌；当前 session 保持登录（用户决议）
		if ($changePassword) Auth::forgetRemember();

		self::redirect('user/settings?saved=1');
	}

	/* ---------------- 封禁（B3-4b；版主/管理员，level≥2） ---------------- */

	/**
	 * POST /user/ban/{username}
	 * duration: 1d|7d|30d|permanent|custom|0(解封)；custom 时读 until 字段（ISO 时间戳）
	 * 防护：不能封自己；不能封 level ≥ 自己的人
	 */
	public function banPost($name = '') {
		if (!Auth::csrfCheck()) self::forbidden();
		$mod = Auth::user();
		if ($mod === null) self::redirect('auth/login');
		if ((int) $mod['level'] < 2) self::forbidden();

		$target = User::get($name);
		if ($target === null) {
			http_response_code(404);
			header('Content-Type: text/plain; charset=utf-8');
			exit('404: 用户不存在。');
		}
		if ((string) $target['username'] === (string) $mod['username']) self::forbidden();
		if ((int) $target['level'] >= (int) $mod['level']) self::forbidden();

		list($until, $untilText) = self::resolveDuration(
			(string) param('duration', '', FALSE),
			(string) param('until', '', FALSE)
		);
		if ($until === false) {
			http_response_code(422);
			header('Content-Type: text/plain; charset=utf-8');
			exit('422: 封禁时长参数无效（custom 需合法的 ISO 时间戳，且晚于当前时间）。');
		}

		$reason = trim(strip_tags((string) param('reason', '', FALSE)));
		$username = (string) $target['username'];
		$wasBanned = User::isBanned($target);
		if (!User::setBan($username, $until, (string) $mod['username'], $reason)) self::forbidden();

		// 私信通知当事用户（版主署名；解封也通知）
		$modName = (string) ($mod['nickname'] ?? $mod['username']);
		if ($until === '') {
			PM::send((string) $mod['username'], $username, '你的账号已解封',
				"你的账号已被版主 {$modName} 解封，欢迎回来。请遵守社区规范。");
		} elseif (!$wasBanned) {
			PM::send((string) $mod['username'], $username, '你的账号已被封禁',
				"你的账号已被版主 {$modName} 封禁至 {$untilText}。"
				. ($reason !== '' ? "原因：{$reason}。" : '')
				. '如有疑问请回复本私信联系版主。');
		}

		self::redirect('user/view/' . rawurlencode($username));
	}

	/**
	 * duration 档位 → (until ISO | '' 解封)；无效返回 [false, false]
	 * 供 user/ban 与举报队列 ban 处理共用
	 */
	public static function resolveDuration($duration, $customUntil = '') {
		$now = time();
		switch ((string) $duration) {
			case '0': return array('', '（已解封）');
			case '1d': return array(date('Y-m-d\TH:i:s', $now + 86400), '1 天后');
			case '7d': return array(date('Y-m-d\TH:i:s', $now + 7 * 86400), '7 天后');
			case '30d': return array(date('Y-m-d\TH:i:s', $now + 30 * 86400), '30 天后');
			case 'permanent': return array(User::BAN_FOREVER, '永久');
			case 'custom':
				$u = trim((string) $customUntil);
				if ($u === '' || $u <= date('Y-m-d\TH:i:s') || strtotime($u) === false) return array(false, false);
				return array($u, $u);
		}
		return array(false, false);
	}

	/* ---------------- 视图与响应辅助 ---------------- */

	private static function render($tpl, array $vars = array()) {
		global $conf, $header, $token;
		extract($vars);

		if (empty($_SESSION['token'])) {
			$token = CSRF::generate();
		}
		$header['title'] = $header['title'] ?? ($conf['title'] ?? 'FlooBB');

		require THEME_DIR . $conf['theme'] . DS . 'header.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . $tpl . '.tpl.php';
		require THEME_DIR . $conf['theme'] . DS . 'footer.tpl.php';
		exit;
	}

	private static function forbidden() {
		http_response_code(403);
		header('Content-Type: text/plain; charset=utf-8');
		exit('403 Forbidden: 权限不足或 CSRF token 无效。');
	}

	private static function redirect($path) {
		header('Location: ' . Router::url($path));
		exit;
	}

	private static function notImplemented($msg) {
		http_response_code(501);
		echo '<!doctype html><meta charset="utf-8"><title>501</title>';
		echo '<p>Phase 0 骨架占位：' . htmlspecialchars($msg) . '</p>';
		echo '<p><a href="' . Router::url() . '">返回首页</a></p>';
	}
}
