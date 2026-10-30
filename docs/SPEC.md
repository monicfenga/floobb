# FlooBB 现代化重构 — 技术规格书 (SPEC)

> 版本：v1.0 (定稿) ｜ 日期：2026-10-30 ｜ 状态：已确认，可进入 Phase 0
> 目标：把 2023 年的半成品原型重做成一套跑在 PHP 8.4 + flat-file JSON 上的现代论坛程序。

---

## 0. 背景与动机

### 0.1 为什么选择 floobb
1. **Free Software**：GPLv3+，可自由改造分发。
2. **快、轻**：无数据库服务器，flat-file 直接跑，部署零依赖。
3. **零安装**：上传即运行，适合个人/小社群。

### 0.2 现状（2023 半成品，已核实）
当前 `M:\laragon\www\floobb` = rosslagerwall 原版 + 做到一半的改造：

| 模块 | 状态 |
|------|------|
| `lib/Flatdb.lib.php` | JSON flat-file 引擎，结构合理，但**全项目仅 `init.php` 用它管 config**，等于摆设 |
| `common.php` 的 `param()` / `xn_url_parse` | Xiuno 风格路由原语，已搬入但**无任何业务调用** |
| `lib/Utils.lib.php` | 日期/相对时间/数字缩写/日志/XSS 过滤，**质量好，直接复用** |
| `lib/CSRF.lib.php` | 存在，待全面接入 |
| `config`（data/config/config.dat.json） | 已配成 "FlooBB neo" + tawian 主题 |
| `theme/tawian/` | 仅 header/footer + 一个写 "hello" 的 homepage，**是骨架**；且远程 HTTP 引 tawian CSS |
| `class.Forum/Topic/Post/User/PM/Poll.php` | **仍读原始 `db/*.dat` 行格式**（`id~name~desc~n~n`），未迁移 |
| `index.php` 路由 switch | **全空 `# code...`**，下方挂着读旧 `.dat` 的死代码 |
| master 的 `login/register/pm/moderate/moveTopic` 等动作文件 | **未移植**到新路由 |
| TinyMCE | 仍在，帖子存 HTML（XSS 面大） |

**参考资产**：`M:\laragon\www\mf-floobb-重新下载版.zip`（你 2023 完整换肤，**已做 theme 解耦**，待解压参考结构，但用的是旧栈）。

### 0.3 目标
- 以 `Flatdb.lib.php` 为**唯一数据层**，彻底弃用 `db/*.dat` 行格式。
- 用 `param()` 路由统一接管所有用户输入与 URL 分发。
- 前端换成 2026 真实栈（Bootstrap 5/MD3 + HTMX + Alpine），**无构建步骤**。
- 保留 GPLv3+、快、零安装三大基因。
- 预留 SNS「动态」能力（缝合 TinySocial 思路，BuddyPress 式时间线）。

---

## 1. 设计原则（硬约束）

1. **无数据库**：所有持久化走 `Flatdb.lib.php` 的 JSON 文件（`data/<type>/<id>.dat.json`）。
2. **PHP 8.4 兼容**：修掉 `E_STRICT`、`track_errors`（PHP 8 已移除/废弃）、`create_function` 等老构造；用 `password_hash()` 替代任何明文/弱哈希。
3. **无构建步骤**：Bootstrap 5 / MD3 / HTMX / Alpine 全部走 CDN；纯 PHP 服务端渲染 + HTMX 局部刷新 + Alpine 轻交互。**禁止** Node/Vite/Python 构建链、禁止 SPA 框架（Vue/React）。
4. **关注点分离**：引擎层（`lib/`）/ 业务控制器（`app/` 或根入口分发）/ 视图层（`theme/<theme>/*.tpl.php`）三层解耦——延续你 2023 拆 theme 的思路。
5. **DRM-Free / GPLv3+**：保留许可证声明，不引入任何闭源/加密机制。
6. **本地只走 Laragon**：开发期一律 `http://127.0.0.1:80`，不让 AI 起临时 php/python/node 服务器。

---

## 2. 技术栈

| 层 | 选型 |
|----|------|
| 运行时 | 目标为PHP 8.4 (Laragon, NTS x64)，不考虑任何后向兼容性 |
| 数据 | `Flatdb.lib.php`（JSON flat-file） |
| 服务端 | 原生 PHP，服务端渲染（无框架或极薄自研路由） |
| 前端基底 | Bootstrap 5（默认主题）/ Tawian-frontend（备选主题，本地 vendored ~23KB，class 近似 Bootstrap）/ MD3（可选） |
| 交互 | HTMX（局部刷新）+ Alpine.js（轻状态）（若不使用Bootstrap 5（因为它已经自带了组件JS，下拉菜单之类的都有），则Alpine.js为必选） |
| 编辑器 | Markdown（已定：marked.js + DOMPurify，仅做实时预览/输入） |
| Markdown 渲染 | 后端 **Parsedown**（吸纳 WebPress/libs/Parsedown.lib.php，PHP 服务端渲染，输出缓存进 `content_html`；SEO/无 JS 友好） |
| 字体 | 默认主题 Noto Sans SC（中文）/ Tawian-frontend 备选主题 Maple Mono（等宽）/ 可选sans-serif？ |
| 代码高亮 | Google Code Prettify（CDN，帖子详情页代码块） |
| 资源 | 全部 CDN（或者把CDN资源都拿到本地使用），现成库直接用 |

---

## 3. 架构

```
index.php                 ← 入口：init + 解析 param(0/1/2) + 分发到控制器
├─ init.php               ← 常量、载入 lib、安装守卫、config、CSRF token
├─ lib/
│   ├─ Flatdb.lib.php     ← 数据层（唯一）；含内存 KV 缓存 + 调试计数（§3.2）
│   ├─ Router.lib.php     ← 原 common.php 的 param()/xn_url_parse，紧邻 Flatdb 之后载入
│   ├─ Utils.lib.php      ← 日期/数字/日志/XSS 过滤（复用 + 吸纳 WebPress/utils）；表单校验逻辑（长度/内容过滤/邮箱/数字）以纯函数纳入，不引入 HTMLForm 类
│   ├─ CSRF.lib.php       ← 防跨站（全面接入；对比吸纳 WebPress/CSRF.lib.php）
│   ├─ Auth.lib.php       ← 登录态/RememberMe/尝试限制/IP 封锁（新增）
│   ├─ Parsedown.lib.php  ← 后端 Markdown 渲染（吸纳 WebPress/libs）
│   ├─ Pagination.lib.php ← 分页（吸纳 WebPress/libs）
│   ├─ Captcha.lib.php    ← 验证码（吸纳 WebPress/libs，注册/防刷）
│   └─ files.lib.php      ← 上传/附件（吸纳 WebPress/libs，限类型+重命名+非执行目录）
├─ app/ 或 controllers/   ← 业务逻辑（forum/topic/post/user/pm/poll/activity/report/mod/search）
├─ model/                 ← 仓储函数层（读 JSON 返回关联数组，业务方法以静态函数组织；**不**引入 OOP 实例实体，见 §3.3）
└─ theme/<theme>/
    ├─ header.tpl.php
    ├─ footer.tpl.php
    ├─ homepage.tpl.php
    └─ ...（各页面片段）
```

> **吸纳 WebPress 组件**：`M:\laragon\www\WebPress\libs` 下有多套可复用组件，按需在 Phase 0/1 吸纳并适配（Parsedown、Pagination、Captcha、files、CSRF/utils 比对合并）。注意 `controller.lib.php` 实为后台访问页、`events.lib.php` 仅为事件日志写入器，均**非**路由/钩子框架，不吸纳作核心。**不吸纳 `HTMLForm.lib.php`**（其 HTML 生成方法硬编码 Bootstrap 类、耦合 Flatboard 全局，违背主题解耦——表单 HTML 改写在主题模板里）；**不吸纳 `toolkit.lib.php`**（纯包装器，`__toStr` 之类即 `strval` 别名，无价值）。**插件机制（Phase 2）参考 TinySocial 的 `Plugin.php` 钩子思路，而非 WebPress `plugin.lib.php`**。

### 3.1 路由（清洁 front-controller + RESTful 段数组 + PATH_INFO 传输）

**传输层（采纳 Flatboard 3.x  fURL 的 PATH_INFO 思路）**
- 单入口 `index.php`。开启 URL 重写后 URL 形如 `/forum/view/123`、`/topic/lock/456`；未开启重写时回退到 `index.php?r=forum/view/123`。
- 路径来源优先级：① `$_SERVER['PATH_INFO']`（如 `/forum/view/123`）；② 回退 `$_GET['r']`；③ 空 → 首页（HomeController::index）。
- 配套 `.htaccess`（Apache/Laragon）：`RewriteRule ^(.*)$ index.php [L]` 把非文件请求转发给入口，其后路径即 PATH_INFO。

**解析层（有序段数组，而非 fURL 的 kv 配对）**
- `Router.lib.php` 把 PATH_INFO 按 `/` 切成**有序段数组**，不做 key/value 配对：
  - `['forum']` → 控制器 `forum`，action 默认 `index`（版块列表）
  - `['forum','add']` → 控制器 `forum`，action `add`
  - `['forum','view','123']` → 控制器 `forum`，action `view`，参数 `123`
  - `['topic','lock','456']` → 控制器 `topic`，action `lock`，参数 `456`
- **三位置契约**：`[控制器, 动作, ...其余参数]`。第 2 段恒为 **action 名**（缺省 `index`）；资源 ID（含**字符串用户名**如 `Geticer`）一律放在 action **之后**，永不挤占 action 槽。原「第 2 段数字=ID、字符串=动作」规则对字符串 ID 资源（用户名 / 版块名）会误判（`/user/Geticer` 会被当成 `action=Geticer`），故弃用。
- **非结构性修饰符走 query string，绝不进路径**：`page`、`sort`、`filter`、`tab`、`order` 等一律经 `param()` 从 `$_GET` 读取，例如 `GET /forum/view/123?page=2&sort=latest`。路径只承载「控制器 / 动作 / 资源层级」，query string 承载「怎么看」。
- **HTTP 方法感知**：`$_SERVER['REQUEST_METHOD']`（GET/POST）参与分发——同名段路由按方法区分「展示表单」与「提交处理」，天然契合 HTMX（`hx-post` 发 POST）。

**路由表（对应你举的例子）**

| 方法 | 路径 | 控制器::action | 语义 | 备注 |
|------|------|----------------|------|------|
| GET | `/` | HomeController::index | 首页 / 版块列表 | 可能包含排序和翻页参数 |
| GET | `/forum/add` | ForumController::add(GET) | 新建版块表单 |  |
| POST | `/forum/add` | ForumController::add(POST) | 提交新建 |  |
| GET | `/forum/view/{fid}` | ForumController::view(fid) | 版块页 | 可能包含排序和翻页参数（?page= / ?sort=） |
| POST | `/forum/order/{fid}` | ForumController::order(fid) | 版块排序 |  |
| POST | `/topic/lock/{tid}` | TopicController::lock(tid) | 锁定主题 |  |
| GET | `/topic/view/{tid}` | TopicController::view(tid) | 主题详情 | 可能包含回帖排序（最新/最旧）、回帖筛选（如只看楼主）和翻页参数（?page=） |
| GET | `/auth/login` | AuthController::login(GET) | 登录表单 |  |
| POST | `/auth/login` | AuthController::login(POST) | 登录提交 |  |
| GET | `/auth/register` | AuthController::register(GET) | 注册表单 | Captcha + CSRF |
| POST | `/auth/register` | AuthController::register(POST) | 提交注册 | 校验邮箱格式/密码强度/用户名唯一；**自动登录（不邮件激活）**；写 users/ + 生成 recovery_codes |
| POST | `/auth/logout` | AuthController::logout | 登出 | CSRF；原 logout.php 为 GET，重建改 POST |
| GET | `/auth/reset` | AuthController::reset(GET) | 密码重置表单（恢复码） | 无邮件；recovery_codes 注册时生成 |
| POST | `/auth/reset` | AuthController::reset(POST) | 提交重置 | 校验 recovery_codes；Phase 3 升级为 TOTP 挑战 |
| GET | `/user/view/{name}` | UserController::view(name) | 用户资料 | 查看他人 |
| GET | `/user/settings` | UserController::settings(GET) | 设置表单 | 登录态；签名/头像/邮箱/改密 |
| POST | `/user/settings` | UserController::settings(POST) | 保存设置 | CSRF；头像走 files.lib.php；改密需验旧密码 |
| GET | `/report/post/{pid}` | ReportController::create(pid) | 举报帖子表单 | 登录态 |
| POST | `/report/post/{pid}` | ReportController::store(pid) | 提交举报(帖子) | CSRF；reason+说明；防自举报/重复 |
| GET | `/report/user/{name}` | ReportController::create(name) | 举报用户表单 | 登录态 |
| POST | `/report/user/{name}` | ReportController::store(name) | 提交举报(用户) | CSRF |
| GET | `/report/topic/{tid}` | ReportController::create(tid) | 举报主题表单 | 登录态（同构） |
| POST | `/report/topic/{tid}` | ReportController::store(tid) | 提交举报(主题) | CSRF |
| GET | `/mod/reports` | ModController::reports | 举报队列 | level≥2 |
| POST | `/mod/resolve/{rid}` | ModController::resolveReport(rid) | 处理（删帖/封禁） | 版主；CSRF |
| POST | `/mod/dismiss/{rid}` | ModController::dismissReport(rid) | 忽略举报 | 版主；CSRF |
| GET | `/pm/inbox` | PMController::inbox | 私信收件箱 | 可能包括翻页，可能包括查看特定收件人的对话 |
| POST | `/poll/vote/{tid}` | PollController::vote(tid) | 投票 |  |
| GET | `/activity` | ActivityController::timeline（Phase 2） | SNS 动态时间线 | 可能包括筛选和翻页 |
| GET | `/search` | SearchController::query | 搜索 | 关键词 `?q=`、范围 `?scope=`（topics/posts/users，可选）、`?page=`（翻页）；**全部走 query string，路径不含关键字** |

其余 controller（`post`/`user`/`pm`/`poll`/`activity`/`report`/`mod`/`search`）类推。~~命名空间 `Controller\{资源}Controller`~~（**已废除**：2026-10-30 起全项目禁 namespace，裸类名 `{资源}Controller`，见 §9）

**嵌套路由**：`/report/post/{pid}` 等，路由器把首段映射为控制器后，把**剩余段数组**整体交给控制器做二次分发（如 `ReportController` 收到 `['post', pid]` → `create(pid)`）。对「动作+单参数」型路由（如 `/mod/resolve/{rid}`），剩余段 `['resolve', rid]` 直接对应 `ModController::resolveReport(rid)`，无需嵌套。

**为什么不采用 fURL 的 kv 配对解析**
- 位置信息丢失：`/forum/123/order/456` 在 kv 模型下变成 `['forum'=>'123','order'=>'456']`，无法区分 `order` 是动作还是嵌套键。
- 资源 ID 与 action 混在同一槽位：`/forum/add` 得到 `['forum'=>'add']`，必须把 `add` 当保留字特判，且若真有叫 "add" 的版块会冲突。
- 不内建 HTTP 方法区分。
- 段数组模型保留顺序、可表达任意深度嵌套资源、与 HTTP 方法组合清晰——更适配论坛 + 未来 SNS 动态路由。

**所有用户输入经 `param()` 取用，禁止 `file()` 直读。**

### 3.2 Flatdb 增强（参考 七濑胡桃粉丝站 Flatboard 3.x  Flatdb）
- **内存 KV 缓存** `$FLATDB_MEMCACHE`：单次请求内缓存已解码的 JSON 条目，避免重复 `file_get_contents` + `json_decode`。
- **调试计数** `$FLATDB_TOTAL_QUERY`（c/r/u/d 路径数组）与 `$FLATDB_TOTAL_QUERY_NUM`（c/r/u/d 计数），仅 `DEBUG_MODE` 时记录，供性能/排错。
- 存储格式**保持 JSON**（`.dat.json`），不切换为 `.dat.php`/var_export 变体。

### 3.3 数据模型模式（关联数组 + 仓储函数层，不用 OOP 实例实体）
- **数据形态 = 关联数组**：Flatdb 经 `json_decode` 天然返回数组，JSON 文件即 schema，不再包一层 OOP 实体。
- **model/ = 仓储函数层**：以命名空间静态函数组织读写与业务判断，操作数组而非对象实例，例如：
  - `Model\Topic::get($id)` → `flatDB::readEntry('topics', $id)`（返回数组或 null）
  - `Model\Topic::listByForum($fid)` / `Model\Topic::create($data)` / `Model\Topic::save($id, $data)`
  - 业务判断也以函数表达：`Model\Topic::isLocked($topic)`、`Model\Topic::canEdit($topic, $user)`（不挂在对象属性上）
- 调用风格：`$topic = Topic::get($id); echo $topic['topicName'];` —— 关联数组写法，逻辑集中在 `model/` 不散落控制器。
- **不采用 OOP 实例实体**（`new Topic(...); $topic->topicName`）：flat-file 下需额外 array↔object 转换层（构造/fromArray/toArray），属冗余样板，违背「无构建、少样板」原则；且主题模板直接消费数组更顺。

---

## 4. 数据模型（JSON flat-file）

> 旧 `db/*.dat` 行格式全部废弃，**不做迁移**：以 Flatdb JSON 全新安装，旧数据不保留（如需可手工处理）。
>
> **newEntry() ID 格式（勿再正则踩坑）**：`Flatdb.lib.php` 的 `newEntry()` 输出 = `date('Y-m-d_H-i-s_') . substr(uniqid(), -5)`，即 `2026-10-30_07-52-23_6eef4` 这种形态——**含下划线、日期与时分秒段用 `-` 分隔**，字符集 `[0-9a-zA-Z_-]`。任何抓取/正则匹配 ID 一律用 `[A-Za-z0-9_\-]+`，禁止假设纯字母数字或纯连字符（已两次因此验收假失败）。适用实体：topics / posts / pms / reports / polls。

```
data/
├─ config/config.dat.json          （已有，扩展字段）
├─ users/<username>.dat.json
├─ forums/<forumId>.dat.json
├─ topics/<topicId>.dat.json
├─ posts/<postId>.dat.json
├─ pms/<username>.dat.json
├─ polls/<topicId>.dat.json
├─ reports/<reportId>.dat.json    （举报，Phase 1）
├─ activity/<activityId>.dat.json  （SNS 动态，Phase 2）
└─ index/                          （可选：版块顺序、用户列表等索引，避免全扫）
```

**users/<username>.dat.json**
```json
{
  "id": "20231030abc12",
  "username": "Geticer",
  "nickname": "Geticer",       // 显示用昵称，可随时改；username 为登录标识，注册后不可变（§9 #12）
  "password_hash": "<password_hash() 输出>",
  "email": "",
  "level": 3,
  "registered": 1793336961,
  "last_seen": 1793336961,
  "signature": "",
  "avatar": "",
  "bio": "",                   // 一句话介绍（知乎风，B3-1 决议；楼层/用户页展示）
  "banned_until": 0,           // B3-4b：unix 时间戳 int；0=未封禁；PHP_INT_MAX=永久（User::BAN_FOREVER）
  "banned_by": "",             // 操作版主用户名
  "banned_reason": "",         // 封禁原因（展示用）
  "recovery_codes": [],        // Phase 1 密码重置用，注册时生成、哈希存储、一次性
  "totp_secret": "",           // Phase 3 TOTP（加密存储；空 = 未启用）
  "totp_enabled": false
}
```
> **封禁语义（B3-4b）**：level 不动，封禁是独立状态。登录（Auth::login）/会话（Auth::user 每请求校验，命中即踢下线+作废 remember）/remember 恢复（Auth::resume 跳过）三处拦截；封禁中的用户等同游客可浏览。写入口唯一：`User::setBan()`（user/ban 路由与举报队列 ban 处理共用）。防护：不能封自己、不能封 level≥自己的人。

**forums/<forumId>.dat.json**
```json
{
  "id": "1",
  "name": "综合",
  "description": "...",
  "order": 0,
  "topic_count": 0,
  "post_count": 0,
  "sticky_topic_ids": []
}
```

**topics/<topicId>.dat.json**
```json
{
  "id": "20231030xyz",
  "forum_id": "1",
  "title": "...",
  "author": "Geticer",
  "created": 1793336961,
  "last_reply_at": 1793337000,
  "views": 0,
  "sticky": false,
  "locked": false,
  "poll_id": null,
  "post_ids": ["..."]
}
```

> ⚠️ `views` 与 `last_reply_at` 为批次 2 实装时新增（SPEC 文本本段即同步处）：`views` 在 topic/view GET 时 +1；`last_reply_at` 回帖时写、首帖=created，版块页排序（replied）与首页最新主题均依赖它。旧数据缺字段走 `??` 兜底 created，不需迁移。

**posts/<postId>.dat.json**
```json
{
  "id": "20231030pqr",
  "topic_id": "...",
  "forum_id": "1",
  "author": "Geticer",
  "created": 1793336961,
  "edited": null,
  "content_md": "**Markdown 源**",
  "content_html": "<p><strong>渲染缓存</strong></p>"
}
```

**pms/<username>.dat.json**（⚠️ 本实体为粗稿：**PM 路由与数据操作清单在批次 3 第一回合盘点时产出并由用户确认后才准实现**——发信/已发送/标记已读/会话查看的路由 SPEC 均未定，如 `POST /pm/send` vs `POST /pm/send/{to}`、显式已读 vs 打开即已读；落定时回填本节与 §3.1 路由表）
```json
{ "inbox": [{"id":"","from":"","to":"","subject":"","body":"","time":0,"read":false}],
  "outbox": [ /* 同上 */ ] }
```

**polls/<topicId>.dat.json**（文件名 = tid；topics.poll_id = tid 或 null；建票只在发主题时一笔提交）
```json
{
  "question": "...",
  "options": [{"text": "", "votes": 0}],
  "votes": {"username": [0, 2]},
  "max_choices": 1,
  "deadline": 1794431600,
  "closed": false,
  "closed_by": ""
}
```

> ⚠️ 批次 3 实装变更（SPEC 文本本段即同步处）：`voters:["user"]` 已废弃，改为 **`votes: { username: [optionIndex,...] }`**（谁投了哪些选项下标；单选=单元素；选项票数按 votes 展开维护 options[i].votes，写路径单入口 `Poll::vote()`）。`max_choices`=1 即单选，>1 多选（投票时选项数≤max_choices，不允许改票）。`deadline` 过期视为自动关闭（只读展示，文件字段不动）；`closed` 由主题作者或 level≥2 手动关闭。投票与回帖独立：锁定主题照常可投。投票人展示用 `nickname ?? username`。

**reports/<reportId>.dat.json**
```json
{
  "id":"",
  "type":"post|user|topic",
  "target_id":"",          // pid / username / tid
  "reporter":"",
  "reason":"spam|abuse|illegal|other",
  "detail":"",
  "created":0,
  "status":"open|resolved|dismissed",
  "resolved_by":"",
  "resolution":""          // 如 "deleted" / "banned" / "warned"
}
```

**activity/<activityId>.dat.json**（Phase 2）
```json
{
  "id":"",
  "type":"status|register|new_topic|new_reply",
  "actor":"Geticer",
  "text":"",            // status 类型的正文
  "ref_id":"",          // 系统事件关联 topic_id / post_id
  "created":0,
  "public":true
}
```

---

## 5. 功能清单

### MVP（Phase 1，必做）
- [ ] 安装/初始化守卫（`init.php` 已有雏形，补全）
- [ ] 注册 / 登录 / 登出（session + RememberMe；CSRF；MAX_LOGIN_ATTEMPT=3；IP 封锁 BLOCK_IP/BLOCK_TIMEOUT）
- [ ] 注册流程：Captcha + CSRF；校验邮箱格式/密码强度/用户名唯一；注册即**自动登录**（**不邮件激活**，避免服务器发信泄露原站 IP 绕过 CDN）；生成 recovery_codes（哈希存储）
- [ ] 密码重置 `/auth/reset`：Phase 1 用 recovery_codes（无邮件）；Phase 3 升级为 TOTP 挑战
- [ ] 版块列表 / 版块页
- [ ] 主题列表 / 主题详情（帖子流，HTMX 分页/无限滚动）
- [ ] 发帖 / 回帖 / 编辑 / 删除（所有者或管理员）
- [ ] 置顶 / 移动 / 锁定（moderate）
- [ ] 短消息 PM（收/发/删）
- [ ] 投票 Poll
- [ ] 搜索 `/search`：关键词/范围/分页全部走 query string（`?q=` / `?scope=` / `?page=`）；flat-file 下做全文扫描 + 简单分词匹配，范围可选 topics/posts/users
- [ ] 举报与版主队列：ReportController（举报帖子/用户/主题，防自举报/重复）+ ModController 举报队列（resolve=删帖/封禁、dismiss，level≥2）
- [ ] 个人资料页 `/user/{name}`（查看他人）
- [ ] 个人设置 `/user/settings`：**昵称**（任意改，即改即生效）/签名/头像（files.lib.php）/邮箱/改密（需验旧密码）
- [ ] 全局统计 + 在线列表

### Phase 2（缝合 TinySocial 思路）
- [ ] **SNS 动态时间线**：用户可直接发动态；系统自动把「注册 / 发帖 / 回帖」写入公共时间线（BuddyPress 式）
- [ ] 插件机制（借鉴 TinySocial 的 `Plugin.php` 钩子，可选）
- [ ] REST API（可选，供外部客户端）

---

## 6. 安全

| 项 | 要求 |
|----|------|
| 密码 | `password_hash()` / `password_verify()`，弃用任何明文/MD5/sha1 直接存 |
| XSS | 输出一律经 `xssFilter`（已有）；若用 Markdown，渲染后再过 DOMPurify/服务端消毒 |
| CSRF | 所有写操作带 `CSRF::generate()` token（已有库，全面接入） |
| 输入 | 所有用户输入走 `param()`，禁 `file()` 直接读 `.dat` |
| 暴力破解 | 登录尝试限制 + IP 封锁（init.php 常量已定义，落实逻辑） |
| 邮件/激活 | **不通过服务器发信做激活/验证码/重置链接**：服务器出站邮件会暴露原站 IP，绕过 CDN 直接 CC/DDoS 原站。改用：注册即自动登录、recovery_codes（Phase 1）、TOTP（Phase 3）。 |
| 文件上传 | 若支持头像/附件，限类型+重命名+非执行目录 |

---

## 7. 前端 / 主题

- `theme/` 解耦：默认主题用 Bootstrap 5 + HTMX + Alpine；MD3 可作为备选主题（theme 机制本就支持多主题）。
- 参考 `mf-floobb` 2023 换肤的**解耦结构**，但改用 2026 栈重做视觉。
- **默认主题 = Bootstrap 5**；**备选主题 = Tawian-frontend**（你 2023 选它的原因：class 近似 Bootstrap、单文件仅 ~23KB，极轻量）。该 CSS 须**下载到本地** `theme/tawian-frontend/tawian-frontend.css`，**禁止**远程 HTTP 引用。
- 响应式 + 可访问性（你长期在意 a11y）。
- 远程 HTTP 引 CSS 的做法**禁止**（原 tawian header 那样），一律本地或 HTTPS CDN。

### 字体与排版
- 基础字号 **16px（1rem = 16px）**，在 `:root` / `html` 显式设定（`font-size: 100%` 即 16px），不依赖浏览器默认缩放。
- **默认主题（Bootstrap 5）通用字体**：`Noto Sans SC`（覆盖中文）+ 系统 `sans-serif` 回退；经 Google Fonts CDN 引入，零构建。
- **备选主题（Tawian-frontend）通用字体**：为贴合其极简 / 代码风，通用字体用等宽之一 —— 默认 **Maple Mono**（中文可读性好），备选 **Iosevka**（https://typeof.net/Iosevka/）。二者均经 CDN 引入。
- 若追求极致轻量（契合 floobb「快、零安装」基因），通用字体也可仅用 `sans-serif`（不加载 web font，最快）。此项可调。

### 代码高亮
- 帖子详情页等出现代码块的位置，使用 **Google Code Prettify**（引入 `prettify.js` + `prettify.css`），对 `pre.prettyprint` 代码块自动高亮。
- Markdown 渲染（marked.js）输出的代码块需带 `prettyprint` class 并触发 `PR.prettyPrint()`。

---

## 8. 数据策略（无旧版迁移）

- **不做旧版迁移**：旧 `db/*.dat` 行格式直接废弃，以 Flatdb JSON 全新安装（安装向导生成 `config` 与首个管理员）。
- 旧 `db/` 目录可保留作归档参考，不参与运行；确认无误后可删。
- 如需从旧数据导入，列为独立手写脚本，不在核心范围内。

---

## 9. 已决议（2026-10-30 用户拍板）

| # | 项 | 决议 |
|---|----|------|
| 1 | 帖子编辑器 | **Markdown**：textarea + marked.js + DOMPurify（均走 CDN），存 Markdown 源、渲染时消毒。弃用 TinyMCE。 |
| 2 | 前端基底 | **Bootstrap 5 默认**；**Tawian-frontend 作备选主题**（本地 vendored，~23KB，class 近似 Bootstrap）；MD3 留作可选。 |
| 3 | SNS 动态阶段 | **Phase 2**：先交付 MVP 论坛，数据模型现在就预留 `activity` 实体。 |
| 4 | Markdown 渲染位置 | **后端 Parsedown**（吸纳 WebPress/libs），服务端渲染并缓存 `content_html`；marked.js 仅做编辑器实时预览。 |
| 5 | lib 组件来源 | 吸纳 WebPress 通用组件：**Parsedown / Pagination / Captcha / files / CSRF / utils**；`controller.lib.php`(后台访问页) 与 `events.lib.php`(事件日志器) 非框架不吸纳。**不吸纳 `HTMLForm.lib.php`**（硬编码 Bootstrap 类、耦合 Flatboard 全局，违背主题解耦——表单 HTML 写进主题模板）；**不吸纳 `toolkit.lib.php`**（纯包装器，`__toStr` 即 `strval` 别名，无价值）；**插件机制（Phase 2）改参考 TinySocial 的 `Plugin.php`，不取 WebPress `plugin.lib.php`**。 |
| 6 | 路由风格 | **RESTful 段数组 + PATH_INFO 传输**（§3.1）：清洁 front-controller，传输层采纳 Flatboard 3.x fURL 的 `PATH_INFO` 思路（带 `?r=` 回退）；解析层用**三位置契约**`[控制器, 动作(缺省 index), ...参数(含资源 ID)]`，而非 fURL 的 kv 配对，也弃用「第2段数字=ID/字符串=动作」规则（字符串用户名会被误判为动作）；HTTP 方法（GET/POST）参与分发；非结构性修饰符（page/sort/filter）走 query string。原 `param()` 等移入 `lib/Router.lib.php`，紧邻 Flatdb 载入。 |
| 7 | 旧版迁移 | **不做迁移**：Flatdb JSON 全新安装，旧 `db/*.dat` 废弃不保留。 |
| 8 | Flatdb 增强 | 参考 Flatboard 3.x Flatdb 增加：内存 KV 缓存 `$FLATDB_MEMCACHE` + 调试计数 `$FLATDB_TOTAL_QUERY` / `$FLATDB_TOTAL_QUERY_NUM`（c/r/u/d），仅 `DEBUG_MODE` 记录；存储保持 JSON。 |
| 9 | 数据模型模式 | **关联数组 + 仓储函数层**（§3.3）：数据形态为关联数组（Flatdb `json_decode` 原生返回），`model/` 以命名空间静态函数组织读写与判断（如 `Model\Topic::get()` / `::isLocked()`），**不引入 OOP 实例实体**（`new Topic` + `toArray` 属冗余样板，违背「少样板」原则）。表单 HTML 直接写在主题模板，由主题 CSS 决定形态。 |
| 10 | 重置密码 | **不邮件**（防原站 IP 泄露绕过 CDN）：Phase 1 用注册时生成的 **recovery_codes**（哈希存储、一次性）；Phase 3 升级为 **TOTP 挑战**（authenticator 证明本人即重置）。管理员可强制重置（level≥3）。 |
| 11 | 举报 / 版主队列 | 新增 **ReportController**（举报 post/user/topic，防自举报/重复）+ **ModController** 举报队列（resolve=删帖/封禁、dismiss，level≥2）；纳入 Phase 1 MVP。 |
| 12 | 用户名 vs 昵称 | **拆分**：`username` = 登录标识 / 文件名 / 唯一键，注册后**不可变**；`nickname` = 显示用昵称，用户可随时在 `/user/settings` 修改（默认初始化为 username）。所有界面展示一律用 `nickname`；提及"显示名"处（导航/帖子作者/时间线等）都取 nickname。 |
| 13 | namespace | **全项目禁用**（2026-10-30 用户拍板）：解析陷阱（裸 `Auth::` 在 namespace 文件里解析成 `Controller\Auth` fatal）靠人肉 `\` 前缀纪律防不住，成本>收益。app/ 与 model/ 全裸类名，autoload 见 init.php。 |
| 14 | 版块 ID | **= slug**（弃用 `newEntry()` 日期串作版块标识）：选填、留空回退 name，填写校验 `[A-Za-z0-9_-]{1,50}`，`Forum::create()` 内查重；URL 形如 `/forum/view/tech`。主题/帖子 ID 维持 `newEntry()`。 |
| 15 | topics 增补字段 | 实装新增 **`views`**（topic/view GET +1）与 **`last_reply_at`**（回帖时写、首帖=created）：支撑「最多查看 / 最新回帖」排序与首页最新主题（§4 已同步）。 |
| 16 | PM 设计前置 | SPEC 对 PM 记录最粗（§4 实体为粗稿、§3.1 仅 1 条路由）：**批次 3 第一回合必须先产出「PM 路由表 + 数据操作清单」经用户确认后才准实现**，落定后回填 §3.1 与 §4。 |

---

## 10. 里程碑（分阶段）

- **Phase 0 — 地基**：修 PHP 8.4 兼容性；`Flatdb` 全面接管（含 §3.2 缓存/调试增强）；`Router.lib.php` 落地清洁路由；Auth/CSRF 接入；吸纳 WebPress 基础组件（Parsedown/Pagination/Captcha 等）。
- **Phase 1 — MVP 论坛**：§5 MVP 全部功能跑通，默认主题成型；含注册/登出/个人设置/举报+版主队列。
- **Phase 2 — SNS 动态 + 打磨**：动态时间线、插件/API（可选）、多主题、a11y 审计。
- **Phase 3 — 身份与安全（TOTP）**：引入 TOTP（Microsoft Authenticator 等）兼容——登录 2FA、密码重置 TOTP 挑战、可选注册激活；彻底摆脱邮件依赖，规避服务器发信泄露原站 IP 的风险。

---

## 11. 编码规约 / 反模式（2026-10-30 定，贯穿全阶段）

### 11.1 `param()` 用法（重点反模式）
`lib/Router.lib.php` 的 `param()` 已重构为**类型推导 + 原始值（不转义）**：

```php
// 返回值类型由默认值决定，调用方绝不再强转：
param('x', '')      → string   // 注意：'123' 仍是 string，不会变 int
param('x', 0)       → int
param('x', 0.0)     → float
param('x', false)   → bool
param('x', [])      → array
param('x', 'def')   → 缺省返回 'def'
// HTML 转义不做——转义在【输出层】做（模板里 htmlspecialchars()）
```

**反模式（禁止，AI 极易复发，已在本项目中系统性清除）：**
```php
// ❌ 以下全部错误：
$action = (string) param('action', '', FALSE);   // (string) 冗余 + FALSE 是旧接口的残留形参，现已无此参数
$pollMax = (int) param('poll_max', 1, FALSE);    // (int) 冗余（默认 1 已是 int），FALSE 已不存在
$sort = (string) Router::query('sort', 'latest');// (string) 冗余
// ✅ 正确：
$action = param('action', '');
$pollMax = param('poll_max', 1);
$sort = Router::query('sort', 'latest');
```

**为什么之前会复发**：旧 `param()` 默认 `htmlspecialchars = TRUE`，所以历史代码被迫传 `FALSE` 取原始值；且 AI 误以为「取参必得 string」所以再 `(string)` 强转。现接口已无 `htmlspecialchars`/`addslashes` 形参，默认值类型即返回值类型，强转纯属噪声。若确需转义，在输出处 `htmlspecialchars()`，不在取参处。

### 11.2 输入来源唯一性
- 所有用户输入经 `param()` / `Router::query()`；**禁直读 `$_GET` / `$_POST` / `$_REQUEST`**（路由层已把 PATH_INFO 段并入 `$_REQUEST`，段参数仍用 `Router::segments()` 取）。
- `param()` 的键可为数字（`param(0)` / `param(1)`）取路径段，但控制器优先用 `Router::segments()`。

### 11.3 输出转义
- 任何用户可控字符串进 HTML，必须经 `htmlspecialchars()`（模板里统一处理）。Markdown 正文走 Parsedown 渲染并经 DOMPurify 消毒后再输出，不套 `htmlspecialchars()`。

### 11.4 时间字段规约
- **所有数据实体里的时间字段一律 unix 时间戳 int**（`time()` 落盘：registered/last_seen/banned_until/created/last_reply_at/edited/time/deadline…），**禁 ISO 字符串入库**。
- 理由：时间是机器生成的内部字段，永远不进用户输入语境，int 无格式歧义（比较/排序直接 `<=>`，不存在 `strcmp` 对 `'9999-…'` 的格式依赖）、无 strtotime 解析失败/时区坑；展示层才 `date('Y-m-d H:i', (int) $x)` 格式化。
- 永久封禁 = `User::BAN_FOREVER`（PHP_INT_MAX）；未封禁 = `0`。
- 唯一例外：flatDB::newEntry() 的 **ID 内嵌日期串**（`Y-m-d_H-i-s_`）不是数据字段，保持原样（id 是字符串键）。
- 2026-10-30 已全量迁移：ISO → int（迁移前 git 快照 bef10eb 可回滚）。

---

## 11. 不在范围内（non-goals）

- 不引入数据库服务器（MySQL/MariaDB/SQLite 文件库）。
  - 因为这个就是flat-file db
- 不做 SPA / Vue / React / Node 构建链。
- 不引入任何构建工具（Vite/webpack）。
- 不做商业化 / DRM / 闭源混淆。
  - 它是GPL V3+ 授权，也难以做闭源和DRM
- 不内置重型富文本（除非 §9 选 TinyMCE）。

---

## 12. 许可证

保留 floobb 原有 GPLv3+ 声明（`COPYING` / `AUTHORS`）；新增代码同协议。重构产物继续自由软件。
