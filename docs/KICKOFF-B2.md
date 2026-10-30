# floobb — Phase 1 批次 2 开工指令：论坛核心

你是 floobb 项目的施工 AI。项目位于 `M:\laragon\www\floobb`。

## 第一步（必做，按序）
1. 完整读取 `docs/SPEC.md` —— §3.1 路由表、§3.3 数据模型模式、§4 forums/topics/posts 实体、§9 已决议条款**不要翻案**。
2. 读取 `.workbuddy/memory/2026-10-30.md` —— 批次 1 全部决策与经验（URL 三坑、curl 竞态、守卫边界等）。

## 本批次范围（forum/topic/post 三控制器闭环）
- [ ] **版块**：ForumController::view(fid)（版块页：主题列表 + 置顶区 + 分页）；index 首页接真实数据（版块卡片带 topic_count/post_count/最后活跃）
- [ ] **主题**：TopicController::view(tid)（帖子流 + 分页 + 排序 ?sort=latest/oldest + 只看楼主 ?filter=author）；POST lock/sticky/move（moderate：版主 level≥2 或作者本人仅限其帖范围，规则按 SPEC）
- [ ] **发帖**：TopicController::create（新主题 = 首帖一并建，写 topics + posts + forum 计数）；PostController::create（回帖，校验未锁定）
- [ ] **编辑/删除**：帖子编辑（所有者或管理员；content_md 重写则重渲染 content_html + 记 edited）；删除（帖子：作者或 level≥2；主题：作者或 level≥2，连带删 posts）
- [ ] **渲染**：发帖存 content_md，服务端 Parsedown 渲染 content_html 缓存（Model\Post::render 已有，接入即可）
- [ ] **分页**：接入 lib/Pagination.lib.php；**$loc 必须传 Router::url() 绝对路径**（memory ④ 遗留项，本批次必须收口）；HTMX 可选（`hx-get` 局部刷帖子流），做不了就整页刷新，不强求
- [ ] 模板：homepage/forum_view/topic_view/topic_create（Markdown textarea + marked.js + DOMPurify 预览，CDN）+ post_edit；复用现有 header/footer
- [ ] 导航/首页接入：登录用户显「新建主题」入口（在版块页）

## 硬约束（违者即错，沿用批次 1）
- **站内 URL 一律 `Router::url()` / `APP_BASE`**，禁相对路径与手写 `/xxx`（KICKOFF.md 已列，本批次新增模板多，重点自查）
- 数据只经 Model\* 静态函数 + flatDB；关联数组，禁 OOP 实体
- 所有写操作 CSRF::check（失败 403）；所有输入经 param()，禁直读 $_GET/$_POST
- 权限：moderate 类动作 level≥2；删除/编辑按所有者或 level 判断（Auth::user()['level']）
- 计数一致性：建/删主题与帖子同步更新 forums 的 topic_count/post_count
- 无构建步骤；Markdown 预览全 CDN（marked.js + DOMPurify + Prettify）
- 不扩大范围：**PM / 投票 / 搜索 / 举报队列 / user settings / 用户列表 全部不做**（批次 3）；tawian 主题不动
- 写操作只限 floobb/；歧义先问；本地只走 `http://127.0.0.1/floobb/`

## 验收（curl 全链路）
1. 建版块 → 建主题（含首帖）→ 版块页/主题页 200 且计数正确
2. 回帖 ×2 → 帖子流顺序与分页正确（造 12 帖验 2 页）
3. 编辑帖子 → content_html 重渲染 + edited 时间写入
4. 锁定后回帖 → 403/拒绝；解锁恢复
5. 删帖/删主题 → 计数同步扣减
6. 未登录建主题 → 跳登录；缺 CSRF → 403
7. 置顶主题出现在版块页置顶区；移动主题后新旧版块计数互换
8. Markdown 帖子含 `**粗体**` / 代码块 → 详情页输出渲染 HTML 且 Prettify class 在场

完成后：memory 记录 + curl 结果贴出，**不自动 commit**。
