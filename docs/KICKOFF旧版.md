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
- **禁止服务器发邮件**：激活 / 验证码 / 重置一律不做邮件（用 recovery_codes；TOTP 是 Phase 3）。
- 本地开发只走 Laragon：`http://127.0.0.1/floobb/`（Apache :80 已在跑）。**禁止自起 python / node / 裸 php.exe 临时服务器**。
- 命令按 pwsh 写；Windows 10 环境。
- 写操作只限 `M:\laragon\www\floobb\`；参考项目一律只读。

## 本次任务：Phase 0 地基（SPEC §10）
1. 修 PHP 8.4 兼容：`init.php` 的 `E_STRICT` / `track_errors` 等 PHP 8 已移除/废弃项，全项目扫一遍老构造（`create_function` 等）。
2. `Flatdb.lib.php` 全面接管 + 增强：内存 KV 缓存 `$FLATDB_MEMCACHE`、调试计数 `$FLATDB_TOTAL_QUERY` / `$FLATDB_TOTAL_QUERY_NUM`（分 c/r/u/d，仅 `DEBUG_MODE` 记录）；存储格式保持 JSON。缓存机制参考：`M:\laragon\www\七濑胡桃粉丝页第五版\discuss\lib\Flatdb.lib.php`。
3. `lib/Router.lib.php`：把 `common.php` 里的 `param()` / `xn_url_parse` 移入并落地 SPEC §3.1 路由（三位置契约 + PATH_INFO + `?r=` 回退 + HTTP 方法感知 + `.htaccess` 伪静态）。
4. 吸纳 WebPress 组件到 `lib/`：`Parsedown.lib.php`（后端 Markdown 渲染）、`Pagination.lib.php`、`Captcha.lib.php`、`files.lib.php`（源：`M:\laragon\www\WebPress\libs`）；CSRF / utils 与现有库比对合并。**不要吸纳 HTMLForm / toolkit / plugin / controller / events**（原因见 SPEC §3 注记）。
5. `lib/Auth.lib.php`：登录态 / RememberMe / 尝试限制（MAX_LOGIN_ATTEMPT=3）/ IP 封锁（BLOCK_IP / BLOCK_TIMEOUT）；`CSRF.lib.php` 全面接入。
6. 搭可跑骨架：`index.php` front-controller 分发 + `model/` 仓储层 + `theme/` 默认主题骨架（Bootstrap 5），至少让首页路由真实跑通。

## 参考资产（只读）
- `M:\laragon\www\WebPress\libs\` —— 组件吸纳源
- `M:\laragon\www\七濑胡桃粉丝页第五版\discuss\lib\` —— Flatdb 缓存 / 调试机制参考
- `M:\laragon\www\mf-floobb-重新下载版.zip` —— 2023 完整换肤（theme 解耦结构参考，旧栈，需自行解压）
- `M:\laragon\www\tinysocial\` —— Phase 2 插件 / 动态参考，**本轮不动**

## 验收与收尾
- 用 `curl`或浏览器技能 访问 `http://127.0.0.1/floobb/` 验证首页路由，并验证伪静态路径（如 `/forum/view/1`）能正确分发。
- 完成后把进展追加到 `M:\laragon\www\floobb\.workbuddy\memory\` 当日日志（该目录 append-only），汇报：改了哪些文件、验证结果、遗留问题。
- Phase 0 只做地基，不抢 Phase 1 的活（版块/主题/发帖等完整业务是下一步）。

## 沟通
结论先行、checkbox 汇报；有歧义先问，不猜；交付哲学：不为极端性能自造轮子，现成库直接用。
