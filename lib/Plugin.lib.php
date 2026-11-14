<?php

/**
 * Plugin.lib.php — 插件机制（Phase 2，SPEC §9 #17）
 *
 * 移植自 tinysocial 的 Plugin.php / PluginManager.php（用户拍板 SPEC §9 #5、#17），
 * 适配 floobb 纪律：禁 namespace、裸类名、Flatdb JSON 数据层、DATA_DIR 集中存储。
 *
 * 结构：
 *  - Plugin：插件基类。构造时反射推断插件名，自动把所有 on* 方法注册为钩子
 *    （onActivate/onDeactivate 为生命周期方法，不注册）。
 *  - Plugin::hook($name, ...$args)：全局钩子分发（tinysocial Event 合并于此）。
 *    处理器返回 false 会中断后续处理器并使 hook() 返回 false。
 *  - PluginManager：发现/启用/停用。注册表 = data/plugins.dat.json（有序数组，
 *    启用顺序 = 加载顺序 = 钩子执行顺序）。
 *
 * 插件约定：plugins/{Name}/{Name}Plugin.php，类名 {Name}Plugin extends Plugin。
 * 插件设置：data/plugins/{Name}.settings.dat.json（经 $this->setting()/setSetting()）。
 */

class Plugin {
	public $name;    // 插件目录名，构造时自动推断
	protected $dir;  // 插件目录绝对路径

	/** @var array<string, callable[]> 全局钩子表 */
	private static $handlers = array();

	public function __construct() {
		$reflector = new ReflectionClass($this);
		$this->dir = dirname($reflector->getFileName());
		$this->name = basename($this->dir);

		// on* 自动注册为同名钩子；生命周期方法除外
		foreach (get_class_methods($this) as $method) {
			if (
				substr($method, 0, 2) === 'on'
				&& !in_array($method, array('onActivate', 'onDeactivate'), true)
			) {
				self::addHandler(substr($method, 2), array($this, $method));
			}
		}
	}

	/* ---------------- 钩子分发 ---------------- */

	public static function addHandler($name, callable $handler) {
		self::$handlers[$name][] = $handler;
	}

	/**
	 * 触发钩子。示例：Plugin::hook('activity_record', $activity);
	 * @return bool 全部处理器执行完毕（未被中断）返回 true
	 */
	public static function hook($name, ...$args) {
		foreach (self::$handlers[$name] ?? array() as $handler) {
			if (call_user_func_array($handler, $args) === false) {
				return false;
			}
		}
		return true;
	}

	public static function hasHandler($name) {
		return !empty(self::$handlers[$name]);
	}

	/* ---------------- 元数据与设置 ---------------- */

	/** 读插件目录下 plugin.json（name/author/version/description），无则空数组 */
	public function metadata() {
		$file = $this->dir . DS . 'plugin.json';
		if (!is_file($file)) return array();
		$data = json_decode((string) file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}

	/** 读插件设置项（data/plugins/{name}.settings.dat.json） */
	public function setting($key, $default = null) {
		$settings = $this->readSettings();
		return array_key_exists($key, $settings) ? $settings[$key] : $default;
	}

	/** 写插件设置项（flatDB 落 data/plugins/{name}.settings.dat.json） */
	public function setSetting($key, $value) {
		$settings = $this->readSettings();
		$settings[$key] = $value;
		return flatDB::saveDB('plugins', $this->name . '.settings', $settings);
	}

	protected function readSettings() {
		$file = DATA_DIR . 'plugins' . DS . $this->name . '.settings.dat.json';
		if (!is_file($file)) return array();
		$data = json_decode((string) file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}

	/* ---------------- 生命周期（PluginManager 触发） ---------------- */

	public function onActivate() {
		return true;
	}
	public function onDeactivate() {
		return true;
	}
}

class PluginManager {
	protected $pluginsDir;
	protected $registryFile;
	protected $enabled = array(); // 有序启用列表（注册表）
	protected $loaded = array();  // 本请求已加载实例

	public function __construct() {
		$this->pluginsDir = PATH_ROOT . DS . 'plugins' . DS;
		$this->registryFile = DATA_DIR . 'plugins.dat.json';
		$this->enabled = $this->readRegistry();
	}

	/** 扫描 plugins/ 目录，返回全部可用插件名（含 {Name}Plugin.php 的子目录） */
	public function scan() {
		$found = array();
		if (!is_dir($this->pluginsDir)) return $found;
		foreach (scandir($this->pluginsDir) ?: array() as $entry) {
			if ($entry === '.' || $entry === '..' || !is_dir($this->pluginsDir . $entry)) continue;
			if (is_file($this->pluginsDir . $entry . DS . $entry . 'Plugin.php')) $found[] = $entry;
		}
		sort($found);
		return $found;
	}

	/** 加载并实例化插件（幂等）。失败返回 false */
	public function load($name) {
		$name = $this->normalize($name);
		if (isset($this->loaded[$name])) return $this->loaded[$name];
		$file = $this->pluginsDir . $name . DS . $name . 'Plugin.php';
		if (!is_file($file)) return false;
		require_once $file;
		$class = $name . 'Plugin';
		if (!class_exists($class)) return false;
		$this->loaded[$name] = new $class();
		return $this->loaded[$name];
	}

	/** 加载全部启用插件（注册表顺序）。init.php 引导时调用 */
	public function loadEnabled() {
		foreach ($this->enabled as $name) {
			$this->load($name);
		}
		return true;
	}

	/** 启用：写注册表 + 加载 + 触发 onActivate */
	public function activate($name) {
		$name = $this->normalize($name);
		if (in_array($name, $this->enabled, true)) return true; // 已启用
		$instance = $this->load($name);
		if ($instance === false) return false;
		$this->enabled[] = $name;
		if (!$this->writeRegistry()) return false;
		return $instance->onActivate();
	}

	/** 停用：触发 onDeactivate + 移出注册表（本请求内已注册钩子保留，页面生命周期无害） */
	public function deactivate($name) {
		$name = $this->normalize($name);
		$key = array_search($name, $this->enabled, true);
		if ($key === false) return true; // 未启用
		$instance = $this->loaded[$name] ?? $this->load($name);
		if ($instance !== false) $instance->onDeactivate();
		unset($this->enabled[$key]);
		$this->enabled = array_values($this->enabled);
		return $this->writeRegistry();
	}

	public function isActive($name) {
		return in_array($this->normalize($name), $this->enabled, true);
	}

	/** 扫描 + 元数据 + 启用状态，供管理界面用 */
	public function available() {
		$list = array();
		foreach ($this->scan() as $name) {
			$instance = $this->load($name);
			$list[$name] = array(
				'active' => $this->isActive($name),
				'metadata' => $instance !== false ? $instance->metadata() : array(),
			);
		}
		return $list;
	}

	protected function normalize($name) {
		return ucfirst(strtolower(trim((string) $name)));
	}

	protected function readRegistry() {
		if (!is_file($this->registryFile)) return array();
		$data = json_decode((string) file_get_contents($this->registryFile), true);
		return is_array($data) ? $data : array();
	}

	protected function writeRegistry() {
		if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
		return file_put_contents(
			$this->registryFile,
			json_encode(array_values($this->enabled), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
			LOCK_EX
		) !== false;
	}
}
