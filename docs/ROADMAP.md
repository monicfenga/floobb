# FlooBB 路线图（ROADMAP）— 未来 AI 会话唯一入口

> 本文件是「下一步做什么、怎么启动」的总索引。新 AI 会话开工前**先读 SPEC.md → 再读本文件 → 再读对应批次的 KICKOFF 文件**，即可无上下文接手。
> 用户（Geticer）精力有限，本文件旨在让他/她不必在脑子里跟踪全部进度。

---

## 0. 当前进度快照（2026-10-30，真实 git 状态）

| 阶段 | 批次 | 状态 | 开工指令 |
|------|------|------|----------|
| Phase 0 | 地基（兼容/Flatdb/Router/组件/Auth/骨架） | ✅ 已 commit | `kickoff.md` / `KICKOFF.md`（历史） |
| Phase 1 | B1 认证闭环 | ✅ 已 commit | （无独立 kickoff，决策见 memory） |
| Phase 1 | B2 论坛核心 | ✅ 已 commit | `KICKOFF-B2.md` |
| Phase 1 | B3-1 个人设置 | ✅ 已 commit（`f7212ab`） | — |
| Phase 1 | B3-2 PM 私信 | ✅ 已 commit（`22db5ac`） | — |
| Phase 1 | **B3-3 Poll 投票** | ✅ **已验收（31/31 curl PASS），待统一 commit** | 见 §3 与 `PROGRESS.md` |
| Phase 1 | **B3-4 举报 + 版主队列** | ⬜ 待开始 | `KICKOFF-B3-4.md` |
| Phase 1 | **B3-5 搜索** | ⬜ 待开始（先出「四问方案」） | `KICKOFF-B3-5.md` |
| Phase 2 | SNS 动态时间线 | ⬜ 待开始 | `KICKOFF-PHASE2.md` |
| Phase 3 | 身份与安全（TOTP） | ⬜ 待开始 | `KICKOFF-PHASE3.md` |

**MVP 论坛收尾信号**：B3-3 提交 + B3-4 + B3-5 完成后，Phase 1 即满血，可进 Phase 2。

---

## 1. 每个未来批次怎么启动

通用规程见 `WORKFLOW.md`（两回合制：第一回合只读盘点交确认，第二回合执行+验收）。启动任一未来批次只需一句话：
> 「按 WORKFLOW.md 启动批次 N，任务是 XXX，开工指令读 `docs/KICKOFF-<批次>.md`」

### B3-3 Poll 投票（✅ 已验收 31/31 curl PASS，待统一 commit）— 已定死的决策（不用再讨论）
- 关联：tid 当文件名，topics.poll_id = tid，建票只在发主题时一笔提交；展示：先投后见（未投票者 HTML 零票数泄露）
- 不许改票；关闭：作者或 level≥2；锁定主题照常可投
- 补偿式写入：建 topic → 建 poll → 回写 poll_id，任一步失败删已写的（验收已模拟失败无孤儿）
- votes 结构：`votes: { username: [optionIndex,...] }`（SPEC §4 已同步）；投票人展示 nickname ?? username
- 选项输入：textarea 一行一个（tagger.js 评估后弃用）；投票参数接收：裸 $_POST + 白名单过滤
- 旧版参考路径：`M:\laragon\www\floobb-master`（repo 里旧文件已删）

### B3-4 举报 + 版主队列（✅ 已验收 31/31 curl PASS，待统一 commit）— 已定死的决策（不用再讨论）
- 拆两子块：4a 举报队列（post/topic）+ 4b 封禁体系（type=user + banned_until，⬜ 待做）
- 处理动作只有删除 / 忽略两种；不做 lock；重复举报追加不拦；队列分待处理/已处理 tab（level≥2）
- 举报入口 = 楼层/主题头内联 details 折叠表单（零 JS）；reason 白名单 spam|abuse|illegal|other
- 删除目标复用既有删除路径（topic 连带 posts+计数+置顶引用+poll；post 走 Topic::save+Forum::adjustCounts）
- 处理后私信通知（发件人=操作版主）：delete 通知双方、dismiss 只通知举报者；口径不同（举报者=感谢反馈，被举报者=违反规范被移除+可回复申诉）
- 实体：data/reports/<id>.dat.json = {type, target_id, reporter, reason, detail, created, status:open|resolved|dismissed, resolved_by, resolution:deleted|no_action|banned}

### B3-4b 封禁体系（✅ 已验收 30/30 curl PASS，待统一 commit）— 已定死的决策（不用再讨论）
- users 加 banned_until/banned_by/banned_reason（SPEC §4 已同步）；永久=9999-12-31T23:59:59（User::BAN_FOREVER）
- level 不动，封禁独立状态；三处拦截：Auth::login 拒登（提示解封时间）/ Auth::user 每请求踢下线+作废 remember / Auth::resume 跳过；封禁用户等同游客可浏览
- 写入口唯一 User::setBan；duration 档位 1d|7d|30d|permanent|custom(ISO)|0 解封（UserController::resolveDuration 共用）
- 防护：不能封自己、不能封 level≥自己（版主封不了版主，管理员可封所有人）
- user 举报：user_view「举报此用户」按钮；队列 user 行处理=封禁（时长下拉）/忽略；自举报拒
- 封禁/解封均私信通知当事用户（版主署名，含原因与时长）
- 举报 resolution 词汇表：deleted / no_action / banned（SPEC §4）

### B3-5 Search（⬜ 第一回合先出四问方案）
- 范围：`ReportController`（举报 post/user/topic，防自举报/重复）+ `ModController`（举报队列 level≥2，resolve=删帖/封禁、dismiss）。
- 锁定决策：SPEC §9 #11；数据模型 `data/reports/<id>.dat.json`（§4）。
- 开工指令：`KICKOFF-B3-4.md`；验收：举报→入队→版主处理/忽略；自举报拒、重复拒、缺 CSRF→403。

### B3-5 搜索（⬜）
- **特殊流程**：第一回合必须先产出《搜索四问方案》（检索范围 / 匹配方式 / 排序相关性 / 分页限额）交用户确认，再编码。
- 范围：`SearchController::query`（GET `/search?q=&scope=&page=`），flat-file 全量扫描 + 简易匹配，复用 Pagination。
- 锁定决策：SPEC §3.1 `/search` 段、§5 MVP 搜索项；**关键词走 query string，路径不含关键字**。
- 开工指令：`KICKOFF-B3-5.md`。

### Phase 2 SNS 动态时间线（⬜）
- 范围：`ActivityController::timeline`（GET `/activity`）+ `model/Activity.php` + 系统事件自动写入（注册/发主题/回帖时 `Activity::record()`）+ 可选直接发动态。
- 锁定决策：SPEC §9 #3（SNS 进 Phase 2）、§4 `activity` 实体、§5 Phase 2。
- 跨切面：需改 auth/topic/post 控制器插入活动记录，**不破坏既有逻辑**。
- 开工指令：`KICKOFF-PHASE2.md`。

### Phase 3 TOTP（⬜）
- 范围：纯 PHP TOTP 实现（`lib/Totp.lib.php`，零构建零依赖）→ 设置内启用（扫码+验证）→ 登录 2FA → 重置挑战升级（替代 recovery_codes 路径）→ 关闭/更换。
- 锁定决策：SPEC §6 邮件禁令、§9 #10（TOTP 升级重置）、users 已预留 `totp_secret`/`totp_enabled`。
- **第一回合先定 TOTP 库引入方式**（vendor 单文件 RFC 6238 实现 vs 自实现），确认后再写。
- 开工指令：`KICKOFF-PHASE3.md`。

---

## 2. 文档清单（镇压文档膨胀，别再新增碎片）

| 文件 | 角色 | 备注 |
|------|------|------|
| `SPEC.md` | **唯一事实来源**：架构/选型/路由/数据模型/安全/已决议 #1–16 | 改它须经用户确认 |
| `WORKFLOW.md` | 两回合制批次执行流程（通用规程） | 启动批次 N 的总依据 |
| `ROADMAP.md` | **本文件**：未来批次索引 + 当前快照 + 各块已定决策 | 新会话入口 |
| `kickoff.md` | Phase 0 开工指令（历史，已完成） | 保留作参考 |
| `KICKOFF-B2.md` | 批次 2 论坛核心开工指令（已完成） | 保留作参考 |
| `KICKOFF-B3-4.md` / `KICKOFF-B3-5.md` / `KICKOFF-PHASE2.md` / `KICKOFF-PHASE3.md` | 未来批次开工指令 | 新会话粘贴即用 |
| `KICKOFF旧版.md` | ⚠️ 与 `kickoff.md` 内容重复（均 Phase 0） | **建议删除其一** |
| `KICKOFF.md`（大写） | ⚠️ 疑似 `kickoff.md` 历史副本 | **建议合并/删除** |
| `完整请求链路.txt` / `请求从开始到结束的完整路径详解.md` | 请求生命周期详解（参考） | 不动 |
| `路由与视图表.mmd` / `模板（视图层）表.mmd` / `请求从开始到结束的完整路径.mmd` | mermaid 可视化（路由/视图/请求流） | 不动，作补充图 |
| `tmp_与DeepSeek的对话片段.txt` | ⚠️ 工作草稿 | **提交前移出 `docs/` 或删除**（见 §3） |

> 原则：新批次只新增一个 `KICKOFF-<批次>.md`，**不要**再散落新的 `tmp_`/`笔记` 类文件。设计讨论产物（如搜索四问）应落进对应 KICKOFF 或本 ROADMAP，而非新文件。

---

## 3. 提交前清理清单（B3-3 提交前必做）

B3-3 Poll 当前**未提交**，git status 显示以下待处理项，提交前请清理：

- [ ] `docs/tmp_与DeepSeek的对话片段.txt` —— 工作草稿，移出 `docs/` 或删除（勿进仓库）。
- [ ] `uploads/avatars/` —— 空目录；若不需进仓库，加进 `.gitignore` 或 `git rm --cached`。
- [ ] `data/测试恢复码.txt`（若存在）—— 测试残留，删除或 `.gitignore`。
- [ ] 确认 `error.log` / `*.log` 已在 `.gitignore`（先前已加，勿重复提交）。
- [ ] Poll 提交信息建议：`feat: 新增投票功能（含先投后见/多选/截止日期/补偿式写入）`。

> 提交动作只在用户明确要求时做；**不主动 push、不建远程**。

---

## 4. 跨批次硬约束（每个 KICKOFF 都重复，此处汇总）

下列违反即错，贯穿 Phase 0 → Phase 3：
1. PHP 8.4（Laragon NTS x64），不考虑后向兼容。
2. 数据层只有 `lib/Flatdb.lib.php`（JSON）；不做旧 `db/*.dat` 迁移。
3. **无构建步骤**：Bootstrap5/HTMX/Alpine/CDN 库；禁 Node/Vite/Python 构建链、禁 SPA 框架。
4. model = **关联数组 + 静态仓储函数**，禁 OOP 实体类。
5. 路由 = 三位置契约 `[控制器, 动作, ...参数]` + PATH_INFO + `?r=` 回退；修饰符走 query string。
6. **站内 URL 一律 `Router::url()` / `APP_BASE`**，禁相对路径与手写 `/xxx`（子文件夹部署 `/floobb/` 下相对路径会被伪静态吞段）。
7. **禁止服务器发邮件**（激活/验证码/重置/2FA 全不走邮件）；用 recovery_codes / TOTP。
8. 所有写操作 `CSRF::check`（失败 403）；所有输入经 `param()`，禁直读 `$_GET/$_POST`。
9. 全项目**禁 namespace**（裸类名）；常量在 `init.php` 已定义的直接引用，不重定义。
10. 本地只走 `http://127.0.0.1/floobb/`；**禁自起临时服务器**；写操作只限 `M:\laragon\www\floobb\`；参考项目只读。
11. **禁 `param()` 冗余强转反模式**（详见 SPEC §11）：`param()` 已按默认值类型返回原始值（string/int/float/bool/array），调用方**不得**写 `(string) param(...)`、`(int) param(...)`，也**不得**传第三参 `FALSE`（`param` 已无 htmlspecialchars 形参）。HTML 转义只在输出层（`htmlspecialchars()` / 模板）做。

---

## 5. 新会话 30 秒接手清单

1. 读 `docs/SPEC.md`（§9 已决议不要翻案）。
2. 读 `docs/ROADMAP.md`（本文件，定位当前批次）。
3. 读对应 `docs/KICKOFF-<批次>.md`（拿任务边界与验收）。
4. 读 `.workbuddy/memory/` 当日及近期日志（决议与上下文）。
5. 按 `WORKFLOW.md` 两回合制启动：**第一回合只读盘点交确认，等用户说"确认"再动手**。
