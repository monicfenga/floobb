<?php namespace Model;

defined('FLOO') or die('potato'); // NOOOO

/**
 * 帖子仓储【model 层 = 关联数组 + 静态仓储函数，SPEC §3.3】
 * 存储：data/posts/<postId>.dat.json（SPEC §4）
 */
class Post {

	public static function get($postId) {
		$postId = (string) $postId;
		if ($postId === '' || !\flatDB::DBexists('posts', $postId)) return null;
		return \flatDB::getDB('posts', $postId);
	}

	public static function listByTopic($topicId) {
		$posts = array();
		foreach (\flatDB::listDB('posts') as $file) {
			$id = basename($file, '.dat.json');
			$p = \flatDB::getDB('posts', $id);
			if (is_array($p) && (string) ($p['topic_id'] ?? '') === (string) $topicId) {
				$posts[] = $p;
			}
		}
		usort($posts, function ($a, $b) {
			return strcmp((string) ($a['created'] ?? ''), (string) ($b['created'] ?? ''));
		});
		return $posts;
	}

	/**
	 * 创建帖子：Markdown 渲染缓存进 content_html（SPEC §9 #4，后端 Parsedown）
	 */
	public static function create(array $data) {
		$id = \flatDB::newEntry();
		$data['id'] = $id;
		$data['content_html'] = self::render($data['content_md'] ?? '');
		\flatDB::saveDB('posts', $id, $data);
		return $id;
	}

	public static function delete($postId) {
		return \flatDB::removeDB('posts', (string) $postId);
	}

	public static function save($postId, array $data) {
		$data['content_html'] = self::render($data['content_md'] ?? '');
		return \flatDB::saveDB('posts', $postId, $data);
	}

	/**
	 * 某作者的帖子数（个人资料页统计；flat-file 全扫，量级小可接受）
	 */
	public static function countByAuthor($author) {
		$n = 0;
		foreach (\flatDB::listDB('posts') as $file) {
			$p = \flatDB::getDB('posts', basename($file, '.dat.json'));
			if (is_array($p) && (string) ($p['author'] ?? '') === (string) $author) $n++;
		}
		return $n;
	}

	/**
	 * Markdown → HTML（Parsedown Extra + safeMode，服务端消毒）
	 */
	public static function render($markdown) {
		$parsedown = new \ParsedownExtra();
		$parsedown->setSafeMode(true);
		$parsedown->setMarkupEscaped(true);
		$parsedown->setBreaksEnabled(true);
		$html = $parsedown->text((string) $markdown);
		// 代码块挂 Prettify class（SPEC §7：pre.prettyprint 自动高亮）
		return str_replace('<pre>', '<pre class="prettyprint">', $html);
	}
}
