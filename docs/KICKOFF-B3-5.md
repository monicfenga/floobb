# floobb — Phase 1 批次 B3-5 开工指令：搜索

你是 floobb 项目的施工 AI。项目位于 `M:\laragon\www\floobb`。前序批次（B1/B2/B3-1/B3-2/B3-3/B3-4）应已完成。

## 第一步（必做，按序）
1. 完整读取 `docs/SPEC.md` —— §3.1 路由表 `/search` 段、§5 MVP 搜索项、§9 已决议。
2. 读取 `docs/ROADMAP.md` 与 `docs/WORKFLOW.md`。

## 本批次特殊流程：先出「四问方案」再编码
**第一回合必须先产出《搜索四问方案》交用户确认，未确认不写代码。** 四问：
1. **检索范围（scope）**：topics / posts / users / 全部？默认范围？`?scope=` 取值与缺省行为。
2. **匹配方式**：flat-file 全量扫描下，substring 包含 / 大小写不敏感 / 按词分词（中文无空格怎么切，建议按字符 n-gram 或标题优先）/ 标题命中优先？是否支持多关键词 AND/OR？
3. **排序与相关性**：按时间倒序 / 按命中位置（标题命中优先于正文）/ 按回复数？是否做简单加权打分（flat-file 下建议轻量）？
4. **分页与限额**：每页条数、结果上限、空查询与超长查询（如 >100 字）如何处理、无结果展示文案。

方案确认后进入第二回合实现。

## 第二回合范围
- [ ] `SearchController::query`（GET `/search?q=&scope=&page=`）：仅从 query string 取参（**路径不含关键字**），经 `param()`。
- [ ] 检索实现：在 `model/` 各实体加 `search($q, $scope)` 静态函数（或独立 `model/Search.php`），flat-file 全量扫描 + 上述匹配的简易实现；结果聚合后分页（复用 `lib/Pagination.lib.php`）。
- [ ] **模板**：`theme/bootstrap/search_form.tpl.php`（搜索框，接入站点 header 或首页）、`theme/bootstrap/search_results.tpl.php`（结果列表 + 分页 + 命中高亮 `<mark>`）。
- [ ] 结果条目链接经 `Router::url()` 指向真实主题/帖子/用户页。

## 硬约束（同上批次，不重复列；有疑先问，不猜）
- 站内 URL `Router::url()`；关联数组；搜索是 GET 无需 CSRF；`param()` 取输入。
- 无构建；**不引入外部搜索引擎**（保持零依赖）；不扩大范围（SNS/TOTP 不做）。
- 写操作只限 floobb/；本地只走 `http://127.0.0.1/floobb/`；禁自起临时服务器。

## 验收
1. `?q=关键词` 返回匹配的主题/帖子/用户；空 q 给提示不报错。
2. `?scope=topics` 仅搜版块主题；分页正确。
3. 命中高亮（`<mark>`）出现；结果链接可达。
4. 超长/特殊字符查询不致命（转义、限长）。

完成后 memory + curl 结果，**不自动 commit**（提交前清理见 ROADMAP §3）。
