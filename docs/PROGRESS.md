# 进度表（人看的，十行以内；每块验收完改一行状态）

Phase 0  地基                    ✅ 已 commit
Phase 1  B1 认证闭环             ✅ 已 commit
Phase 1  B2 论坛核心             ✅ 已 commit（含 slug/去 namespace）
Phase 1  B3-1 个人设置           ✅ 已验收已 commit（f7212ab）
Phase 1  B3-2 PM 私信            ✅ 已验收已 commit（22db5ac）
Phase 1  B3-3 Poll               ✅ 已验收（31/31 curl 全链路 PASS，含先投后见/补偿回滚；待统一 commit）
Phase 1  B3-4 Report+Mod         ⬜ 待开始
Phase 1  B3-5 Search             ⬜ 待开始（先出四问方案）

## Poll 已定死的决策（不用再讨论）
- 关联：tid 当文件名，topics.poll_id = tid，建票只在发主题时一笔提交
- 展示：先投后见——未投票用户 HTML 里不得出现任何票数/百分比（连 0 票都不露）
- 不许改票；关闭：作者或 level≥2；锁定主题照常可投
- 建主题+建票做补偿式写入：先建 topic 拿 tid → 建 poll → 回写 poll_id，任一步失败删掉已写的
- voters 结构：votes: { username: [optionIndex, ...] }（模型层已按此实现，SPEC §4 已同步）
- 结果列投票人用 nickname ?? username
- 选项输入：textarea 一行一个（tagger.js 评估后弃用）；投票参数接收：裸 $_POST + 白名单过滤
- 旧版参考路径：M:\laragon\www\floobb-master（repo 里旧文件已删）

## Poll 剩余待办
（无——全部完成并验收通过）
