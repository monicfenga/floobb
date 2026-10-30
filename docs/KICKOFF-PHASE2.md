# floobb — Phase 2 开工指令：SNS 动态时间线

你是 floobb 项目的施工 AI。项目位于 `M:\laragon\www\floobb`。Phase 1 MVP 论坛应已完成（含 B3-3 投票、B3-4 举报、B3-5 搜索）。

## 第一步（必做，按序）
1. 读取 `docs/SPEC.md` —— §3.1 `/activity` 段、§4 `activity` 实体、§5 Phase 2、§9 #3（SNS 进 Phase 2）。
2. 读取 `docs/ROADMAP.md`、`docs/WORKFLOW.md`。

## 本批次范围
- [ ] **ActivityController::timeline**（GET `/activity`）：公共时间线，分页（`?page=`），可按 `?type=status|register|new_topic|new_reply` 筛选。
- [ ] **model/Activity.php**：`create($data)` / `listTimeline($page)` / `listByActor($user)` / `listByType($type)`。数据落 `data/activity/<id>.dat.json`（schema §4）。`public` 字段控制可见性。
- [ ] **系统事件自动写入（跨切面，需改现有控制器）**：
  - 注册成功 → 写 `type=register`（`AuthController::register` POST）。
  - 发主题 → 写 `type=new_topic`（`TopicController::create`）。
  - 回帖 → 写 `type=new_reply`（`PostController::create`）。
  - 集成点：对应控制器提交成功后追加 `Activity::record(...)`；**不破坏既有逻辑**，抽一个小函数便于插入。
- [ ] **直接发动态（MVP 先做）**：`GET/POST /activity` 或 `/status` 让用户发纯文本动态（`type=status`，存 `text`）；POST 需 CSRF、登录态。
- [ ] **模板**：`theme/bootstrap/activity_timeline.tpl.php`（时间线卡片：头像/昵称/动作/时间/关联链接）+ 发布框。
- [ ] **插件机制（可选）**：参考 `M:\laragon\www\tinysocial\Plugin.php` 钩子思路，提供 `Plugin::hook('activity_record', ...)` 之类扩展点；非必需，按需。

## 硬约束
- 站内 URL `Router::url()`；关联数组；写操作 CSRF；`param()` 取输入。
- 活动写入不得拖慢主流程（失败静默日志，不阻断发帖/注册）。
- 无构建；不扩大范围（TOTP 属 Phase 3，本批次不做登录 2FA）。

## 验收
1. 注册/发主题/回帖后，`/activity` 时间线出现对应系统事件（actor 昵称、关联链接可达）。
2. 发一条动态 → 出现在时间线顶部；分页正确。
3. 筛选 `?type=new_topic` 只显示发主题事件。
4. 未登录访问时间线可看（public）；发动态需登录。

完成后 memory + curl 结果，**不自动 commit**。
