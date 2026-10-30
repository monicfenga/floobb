# floobb — Phase 3 开工指令：身份与安全（TOTP）

你是 floobb 项目的施工 AI。项目位于 `M:\laragon\www\floobb`。前序 Phase 完成。本批次目标：引入 TOTP（Microsoft Authenticator 等），彻底摆脱邮件依赖。

## 第一步（必做，按序）
1. 读取 `docs/SPEC.md` —— §4 users 实体（`totp_secret` / `totp_enabled` 已预留）、§5 密码重置、§6 安全（邮件禁令）、§9 #10（TOTP 升级重置）。
2. 读取 `docs/ROADMAP.md`、`docs/WORKFLOW.md`。

## 背景与硬约束
- **绝不通过服务器发邮件**（§6）：激活/验证码/重置一律不做邮件。TOTP 正是为彻底摆脱邮件依赖、规避服务器发信泄露原站 IP 绕过 CDN 的风险。
- 用户数据已预留 `totp_secret`（加密存储，空=未启用）、`totp_enabled`（bool）。

## 本批次范围
- [ ] **TOTP 库**：flat-file 项目无 Composer。**第一回合须先定"如何引入纯 PHP TOTP 实现"**——建议 vendor 一个单文件 RFC 6238 实现（如 spomky-labs/otphp 的单文件变体，或自实现 base32 + HOTP/TOTP 约 80 行），放进 `lib/Totp.lib.php`，零构建、零依赖。确认后再写。
- [ ] **启用流程**：`UserController::settings` 增「开启两步验证」——生成随机 base32 secret → 输出 `otpauth://` provisioning URI → 前端用现成 QR 库（CDN，如 qrcode.js）渲染二维码 → 用户输入当前 6 位码验证 → 验证通过写 `totp_enabled=true` + 加密 `totp_secret`；同时展示**备用恢复码**（复用已有 `recovery_codes`）。
- [ ] **登录 2FA**：`AuthController::login` 在密码验证通过后，若 `totp_enabled` → 进入第二因子挑战页（输入 6 位码），校验通过才建 session。
- [ ] **重置挑战升级（§9 #10）**：`/auth/reset` 在 Phase 1 用 recovery_codes；本批次新增 TOTP 路径——已启用 TOTP 的用户可用 TOTP 码重置密码（替代/补充 recovery_codes）。管理员强重置（level≥3）保留兜底。
- [ ] **关闭/更换**：settings 内可关闭（需验当前 TOTP 或密码）或重新生成 secret。
- [ ] **模板**：settings 内的 TOTP 区块（二维码 + 输入框 + 恢复码展示）、登录 2FA 挑战页。

## 验收
1. 开启 TOTP：扫码 → 输入码验证 → 数据库 `totp_enabled=true`、`secret` 非空（加密）。
2. 登录已启用用户：密码对但仍需 6 位码；错码拒；对码进 session。
3. 重置：用 TOTP 码走通重置；未启用用户仍走 recovery_codes。
4. 关闭 TOTP：需验证；关闭后登录仅密码。
5. 无邮件发信逻辑混入。

完成后 memory + curl 结果，**不自动 commit**。
