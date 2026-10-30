# floobb 现代化 — Phase 0 开工指令

你是 floobb 现代化重构项目的施工 AI。项目位于 `M:\laragon\www\floobb`。

## 第一步（必做，按序）
1. 完整读取 `M:\laragon\www\floobb\docs\SPEC.md` —— 唯一事实来源（v1.0 定稿）。所有架构 / 选型 / 路由 / 数据模型 / 安全决策已在其中，**§9 已决议的 11 条不要重新提问、不要翻案**；有实现层面的歧义再问。
2. 读取 `M:\laragon\www\floobb\.workbuddy\memory\2026-10-30.md` —— 决策过程与 2023 现状复原记录。

## 项目一句话
把 2023 年的 floobb 半成品原型（rosslagerwall 原版 + 做到一半的 Flatdb / 主题改造）重写为 PHP 8.4 + flat-file JSON 的现代论坛。GPLv3+。

## 硬约束（违者即错）
- PHP 8.4（Laragon, NTS x64），不考虑向后兼容。
- 数据层只有 `lib/Flatdb.lib.php`（JSON）；**不做旧 `db/*.dat` 迁移**，全新安装。
- **无构建步骤**：Bootstrap 5 / HTMX / Alpine / marked.js / DOMPurify / Google Code Prettify 全走 CDN 或本地 vendored；禁止 Node / Vite / Python 构建链，禁止 SPA 框架。
- model 层 = **关联数组 + 静态仓储函数**（`Model\Topic::get()` 返回数组），禁止 OOP 实体类。
- 路由 = 显式三位置契约 `[控制器, 动作(缺省 index), ...参数]`，PATH_INFO 传输 + `?r=` 回退；page / sort / filter 等修饰符一律 query string，不进路径。
- **站内 URL 一律走 `Router::url()` / `APP_BASE` 绝对路径（模板 href/src/action、控制器 redirect、库输出的资源 URL 都算）；禁止相对路径与手写 `/xxx` 根路径**。子文件夹部署（/floobb/）下相对路径会被伪静态 URL 吞段、手写根路径会丢 /floobb 前缀——该坑已出现三次（导航、验证码图片、及同类风险），验收必查。
- **禁止服务器发邮件**：激活 / 验证码 / 重置一律不做邮件（用 recovery_codes；TOTP 是 Phase 3）。
- 本地开发只走 Laragon：`http://127.0.0.1/floobb/`（Apache :80 已在跑）。**禁止自起 python / node / 裸 php.exe 临时服务器**。
- 命令按 pwsh 写；Windows 10 环境。
- 写操作只限 `M:\laragon\www\floobb\`；参考项目一律只读。
- **不扩大改动范围**（新增，重点）：
  - 只修改本指令明确列出的文件与函数；**不重命名、不调整现有代码风格、不替换框架结构、不顺手"现代化"无关代码**。
  - 需要额外改动才能完成时，**先列出方案等我确认，不要直接改**。
  - 任何解压 / 临时产物也只能落在 `M:\laragon\www\floobb\` 内，不得写到该目录之外。

## 执行方式：两回合制（本轮只做第一回合）

### 第一回合（本轮唯一交付）：只读盘点，**不修改任何文件**
完成第一步阅读后，先产出以下三张清单交我确认，**本轮结束即停，不要动手改任何代码**：

1. **全项目老构造扫描清单**：逐条列出「文件路径 + 行号 + 命中的构造名」，扫描范围见下方《PHP 8 兼容检查清单》。
2. **WebPress 组件吸纳映射表**：吸纳哪个源文件 → 落到 `lib/` 哪个文件 → 需要做哪些适配改动（逐条列明适配点）。
3. **文件影响面清单**：Phase 0 六项任务各自会新增 / 修改 / 删除哪些文件。其中第 3 项搬迁 `param()` / `xn_url_parse` 时，**必须先 `grep` 全项目引用点，把所有调用点一一列出**。

**等我回复"确认"后，才进入第二回合。**

### 第二回合（待我确认后再执行）：Phase 0 地基（SPEC §10）
1. 修 PHP 8.4 兼容：按第一回合清单，逐个修掉 `init.php` 的 `E_STRICT` / `track_errors` 及 `create_function` 等老构造。**只修清单内命中的项，不顺手扩改。**
2. `Flatdb.lib.php` 全面接管 + 增强：内存 KV 缓存 `$FLATDB_MEMCACHE`、调试计数 `$FLATDB_TOTAL_QUERY` / `$FLATDB_TOTAL_QUERY_NUM`（分 c/r/u/d，仅 `DEBUG_MODE` 记录）；存储格式保持 JSON。缓存机制参考：`M:\laragon\www\七濑胡桃粉丝页第五版\discuss\lib\Flatdb.lib.php`。
3. `lib/Router.lib.php`：把 `common.php` 里的 `param()` / `xn_url_parse` 移入并落地 SPEC §3.1 路由（三位置契约 + PATH_INFO + `?r=` 回退 + HTTP 方法感知 + `.htaccess` 伪静态）。**搬迁前按第一回合的影响面清单核对全部调用点，删原定义前确认无遗漏。**
4. 吸纳 WebPress 组件到 `lib/`：`Parsedown.lib.php`（后端 Markdown 渲染）、`Pagination.lib.php`、`Captcha.lib.php`、`files.lib.php`（源：`M:\laragon\www\WebPress\libs`）。**CSRF / utils 一律按"增量补函数、现有库接口不变"处理**（现有 `Utils.lib.php` 质量好，不得大改或替换）。**不要吸纳 HTMLForm / toolkit / plugin / controller / events**（原因见 SPEC §3 注记）。
5. `lib/Auth.lib.php`：登录态 / RememberMe / 尝试限制（`MAX_LOGIN_ATTEMPT=3`）/ IP 封锁（`BLOCK_IP` / `BLOCK_TIMEOUT`）；`CSRF.lib.php` 全面接入。**上述常量已在 `init.php` 定义，直接引用，不要重定义。**
6. 搭可跑骨架：`index.php` front-controller 分发 + `model/` 仓储层 + `theme/` 默认主题骨架（Bootstrap 5），至少让首页路由真实跑通。**只搭骨架，不做 Phase 1 的业务功能。**

## PHP 8 兼容检查清单（第一回合按此扫描，目标运行时 PHP 8.4）
- **PHP 8.0 已整体移除**：`each()`、`money_format()`、`get_magic_quotes_gpc()`、`get_magic_quotes_runtime()`、`__autoload()`（改用 `spl_autoload_register`）、`restore_include_path()`、`fgetss()`、`create_function()`、`assert()` 的字符串形式；以及若残留的 `mysql_*` / `mcrypt_*` 系列函数。
- **已移除的变量 / 常量**：`track_errors`（连带 `$php_errormsg`）、`E_STRICT`（8.4 已弃用，注意 `error_reporting(E_ALL | E_STRICT)` 这类写法）。
- **语法级变更（PHP 8.0）**：花括号字符串 / 数组下标 `$str{0}` → 须改为 `$str[0]`（老论坛 / 模板代码里常见）；`(real)` 强制转换改 `(float)`；`(unset)` 强制转换移除。
- **弃用（PHP 8.2+）**：隐式动态属性（给对象随手挂属性）、`utf8_encode()` / `utf8_decode()`。
- 命中即登记；清单以外的疑似兼容风险，一并放在清单末尾"待定"区，由我判断是否处理。

## 参考资产（只读）
- `M:\laragon\www\WebPress\libs\` —— 组件吸纳源
- `M:\laragon\www\七濑胡桃粉丝页第五版\discuss\lib\` —— Flatdb 缓存 / 调试机制参考
- `M:\laragon\www\mf-floobb-重新下载版.zip` —— 2023 完整换肤（theme 解耦结构参考，旧栈，需自行解压）
- `M:\laragon\www\tinysocial\` —— Phase 2 插件 / 动态参考，**本轮不动**

## 验收与收尾
- pwsh `php -v` 确认 8.4；用 `curl` 打 `http://127.0.0.1/floobb/` 验证首页路由，并验证伪静态路径（如 `/forum/view/1`）能正确分发。
- 完成后把进展追加到 `M:\laragon\www\floobb\.workbuddy\memory\` 当日日志（该目录 append-only），汇报：改了哪些文件、验证结果、遗留问题。
- Phase 0 只做地基，不抢 Phase 1 的活（版块/主题/发帖等完整业务是下一步）。

## 沟通
结论先行、checkbox 汇报；有歧义先问，不猜；交付哲学：不为极端性能自造轮子，现成库直接用。
