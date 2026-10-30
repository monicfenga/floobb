# floobb — Phase 1 批次 B3-4 开工指令：举报 + 版主队列

你是 floobb 项目的施工 AI。项目位于 `M:\laragon\www\floobb`。Phase 1 前序批次（B1/B2/B3-1/B3-2）已完成，B3-3 Poll 已编码待提交。

## 第一步（必做，按序）
1. 完整读取 `docs/SPEC.md` —— §3.1 路由表（report / mod 段）、§4 `reports` 实体、§9 已决议 #11（举报/版主队列）、#12（用户名/昵称拆分）。**§9 已决议不要翻案**。
2. 读取 `docs/ROADMAP.md` —— 当前进度与下游边界。
3. 读取 `docs/WORKFLOW.md` —— 两回合制；本轮先只做第一回合盘点。

## 本批次范围（ReportController + ModController 闭环）
- [ ] **举报入口**：`ReportController::create(type, id)`（GET 表单）+ `store(type, id)`（POST 提交）。`type ∈ post|user|topic`，`id` 为 `pid` / `username` / `tid`。
  - 防自举报：举报 post/user/topic 时若 target 归属当前用户 → 拒绝。
  - 防重复：同一 reporter 对同一 `(type, target_id)` 已存在 `open` 报告 → 拒绝（提示"已举报"）。
  - 校验 `reason ∈ spam|abuse|illegal|other`；`detail` 选填；`CSRF::check`。
- [ ] **版主队列**：`ModController::reports`（GET，level≥2）→ 列出 `status=open` 的报告（可按 `?type=` 筛选）。
  - `resolveReport(rid)`（POST）：依 type 执行处置——post→删帖（`Post::delete` 连带 forum/topic 计数扣减）、user→封禁（level 降/标记 banned）、topic→删主题；写 `reports.status=resolved` + `resolution` + `resolved_by`；CSRF。
  - `dismissReport(rid)`（POST）：`status=dismissed`；CSRF。
- [ ] **model/Report.php**：`create()` / `get()` / `listOpen()` / `listByType()` / `resolve()` / `dismiss()`。数据落 `data/reports/<id>.dat.json`（schema 见 SPEC §4）。
- [ ] **模板**：`theme/bootstrap/report_form.tpl.php`（举报表单，可独立页或 modal）；`theme/bootstrap/_reports.tpl.php`（版主队列页，含 resolve/dismiss 按钮与 CSRF token）。
- [ ] **入口接线**：在帖子视图（topic_view 每帖）、用户资料页、主题视图加"举报"链接（一律 `Router::url('/report/post/'.$pid)` 等）；版主导航加"举报队列"入口（仅 level≥2 显）。

## 硬约束（沿用前序批次，违者即错）
- 站内 URL 一律 `Router::url()` / `APP_BASE`，禁相对路径与手写 `/xxx`。
- 数据只经 `Model\*` 静态函数 + flatDB；关联数组，禁 OOP 实体。
- 所有写操作 `CSRF::check`（失败 403）；所有输入经 `param()`，禁直读 `$_GET/$_POST`。
- 权限：moderate 类动作 `level≥2`；处置动作校验操作者权限。
- 无构建步骤；不扩大范围（搜索 / SNS / TOTP 不做，留待后续批次）。
- 写操作只限 `M:\laragon\www\floobb\`；歧义先问；本地只走 `http://127.0.0.1/floobb/`；禁自起临时服务器。

## 验收（curl 全链路）
1. 普通用户举报某帖 → `data/reports/` 新增 open 记录；举报自己帖子 → 拒。
2. 同目标重复举报 → 拒（提示已举报）。
3. level≥2 访问 `/mod/reports` → 看到该报告；缺 CSRF → 403。
4. resolve 删帖 → 帖子消失、forum/topic 计数同步扣减；reports 状态=resolved。
5. dismiss → 报告从队列消失（status=dismissed）。
6. 未登录举报 → 跳登录；非版主访问 `/mod/reports` → 403。

完成后：memory 记录 + curl 结果贴出，**不自动 commit**（提交前清理见 ROADMAP §3）。
