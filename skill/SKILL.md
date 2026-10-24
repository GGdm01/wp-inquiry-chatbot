---
name: wp-inquiry-chatbot-setup
description: WordPress 外贸独立站 B2B 询盘机器人（wp-inquiry-chatbot）插件升级、业务产品线深度分析、发散式追问表单定制与端到端自动化配置
argument-hint: "[site-domain-or-port] [feishu-webhook-url (optional)]"
---

# WordPress B2B 询盘机器人自动化配置与语料定制技能 (wp-inquiry-chatbot-setup)

本技能用于为各类 B2B 外贸制造独立站（如商用自助终端 Kiosk、工业触控屏、条形液晶屏、商用照明、工程机械耐磨件等）快速完成 `wp-inquiry-chatbot` 的版本升级、四维立体产品线与买家定制要素深度分析、业务专属发散式追问表单流（Form & Probing Questions）定制、飞书 Webhook 自动化挂载测试，以及保存为站点专属永久默认预设。

---

## 一、核心设计哲学与铁律

1. **第 1 步绝对强校验商业邮箱门禁（Upfront Direct Email Gate）**：
   - 彻底废除传统的先问称呼、公司名的冗余步骤；第 1 步直接要求买家提供带 `@` 的工作邮箱（配以“发送 CAD 钣金图纸与出厂阶梯批发报价单”的利益诱导）。
   - 用户输入有效邮箱的瞬间，线索立即入库持久化并推送到销售端，彻底杜绝买家在后续多轮问答中途跳出造成的线索流失。
2. **拒绝平庸业余的通用屏提问**：
   - 严禁对所有站千篇一律地只问“屏幕尺寸”、“带不带触控”。
   - 必须先深度分析站点产品线与规格属性，挖掘买家发询价单（RFQ）时的核心工程要素（如 Kiosk 站关注“安装形态、外设模块开孔、打印机/扫码/发卡器、工控主机方案”；工业屏站关注“贴合工艺、安装方式、宽温防水、接口规范”）。
3. **极简轻量点选（Low Friction Mobile-Friendly）**：
   - 后续步骤优先提供 3~4 个典型工程预设选项（支持一键点选或自由输入），极大降低移动端与桌面端的打字阻力。
4. **后台与飞书卡片 2~4 字标签映射**：
   - 所有问题 ID 与提问文案需确保被后端规整为紧凑的 2~4 字中文规格标签（如 `【安装形态】`、`【外设模块】`、`【屏幕尺寸】`、`【主控系统】`、`【预估批量】`）。
5. **定制表单固化为专属默认预设（防手误重置丢失）**：
   - 配置完成后必须自动调用 `save_custom_default` 将配置快照写入 `wp_options`（`wpi_custom_default_questions`），确保后续即使误点预设重置也能一键恢复。
6. **飞书通知的主动提问门禁**：
   - 检查或接收飞书 Webhook 地址，若未提供且站点未配置，必须使用 `ask` 工具主动向用户提问确认。

---

## 二、双轨执行架构（服务器一键部署 vs CDP 浏览器配置）

```mermaid
graph TD
    Start{判定执行环境与权限} -->|拥有服务器 SSH / 容器 / CLI 权限| TrackA[轨道 A: 服务器端极速一键部署<br/>⚡ 耗时仅需 1~2 秒 · 强烈推荐]
    Start -->|仅拥有 WP 管理员账号 / CDP 调试端口| TrackB[轨道 B: 浏览器 CDP / 后台配置流<br/>耗时 2~3 分钟 · 备用通道]
    
    TrackA --> Verify[前台实机终端验证与线索流测试]
    TrackB --> Verify
```

---

### 🚀 轨道 A：服务器端极速自主更新与一键部署（推荐 · 1~2 秒完成全流程）

在服务器终端执行时，**AI 必须自主处理代码源与版本状态，绝不死板硬编码版本号**：

#### 1. 代码源优先级与版本自治机制（有就更新，没有就装，接受显式指定）：
* **优先支持用户指定目录**：若命令中显式传入了本地/指定代码目录（如 `--plugin-src=/path/to/wp-chatbot` 或从当前开发机 rsync 同步），**直接接受指定**，优先使用指定目录的代码覆盖就位，0 网络延迟；
* **未指定时全自动远端校验**：自动探测 Gitea (`192.168.31.16:22610` / `gitgit.8268888.xyz`) 或 GitHub (`GGdm01/wp-inquiry-chatbot`) 的最新 Release 版本号：
  - **未安装**：自动拉取官方最新版本全新安装；
  - **已安装但非最新**：自动下载最新代码覆盖热更新，并自动触发 `WPI_DB::create_tables()` 增量表迁移；
  - **已是最新版**：直接跳过拉取，秒级执行业务配置。

```bash
# 场景 1：默认自动模式（自动比对远端版本：没有就装，有新就更）
php wp-content/plugins/wp-inquiry-chatbot/bin/wpi-quick-setup.php \
  --industry=kiosk \
  --feishu="https://open.feishu.cn/open-apis/bot/v2/hook/xxx" \
  --whatsapp="+8618098949445" \
  --test-feishu

# 场景 2：接受显式指定目录（直接将指定源码目录秒级同步覆盖）
php wp-content/plugins/wp-inquiry-chatbot/bin/wpi-quick-setup.php \
  --plugin-src="/path/to/wp-chatbot" \
  --industry=touch_screen \
  --feishu="https://open.feishu.cn/open-apis/bot/v2/hook/xxx" \
  --test-feishu
```

**参数支持：**
* `--plugin-src=<目录>`：直接指定本地/服务器上的插件代码源目录，直接同步覆盖生效。
* `--industry`：`interactive_projection` (儿童室内互动体感设备/AR投影/互动砸球/AR沙滩沙桌/蹦床滑梯), `solar_charging` (太阳能光伏手机充电站/智慧光伏长椅/景区充电亭), `smart_board` (教学与会议智能平板一体机/交互白板), `retail_signage` (商用数字标牌广告机), `outdoor_lcd` (户外高亮防水液晶屏/智慧公交站牌), `video_wall` (超窄边液晶拼接屏电视墙/监控指挥大屏), `interactive_table` (智能触控茶几/会议触控桌/展厅互动台), `kiosk` (自助终端整机), `touch_screen` (工业触控屏), `stretched_bar` (条形液晶屏), `lighting` (商业照明), `wear_parts` (机械耐磨件), `battery` (储能锂电), `generic` (工业通用)。
* `--corpus=/path/to/custom.json`：直接指定自定义 7 步表单文件。
* `--feishu` & `--secret`：飞书机器人 Webhook 与签名密钥。
* `--test-feishu`：配置后立刻向外贸群推送交互卡片验证连通性。
* `--clean-cache`：自动清理 WP Super Cache、LiteSpeed、Seraphinite 等整站缓存。
---

### 🖥️ 轨道 B：浏览器 CDP / WP Admin 后台配置（备用）

在无服务器 SSH / CLI 权限时，走原有的 6 大标准步：

### Step 1：自主核验插件版本并升级至 GitHub 官方最新版 (v1.4.5+)

1. **官方最新源**：
   - 官方最新 Release ZIP：`https://github.com/GGdm01/wp-inquiry-chatbot/archive/refs/heads/main.zip`
   - 版本元数据探针：`https://raw.githubusercontent.com/GGdm01/wp-inquiry-chatbot/main/info.json`
2. **连接目标浏览器 CDP 调试端口**：
   ```bash
   curl -s http://127.0.0.1:<PORT>/json/list
   ```
3. **登录后台核验与自主升级**：
   - 访问 `/wp-admin/plugins.php`，检查 `WP Inquiry Chatbot (ECV Style)` 插件版本。
   - 若未安装或版本低于 `1.4.5`：
     - **自主升级方式 A（原生热更）**：访问 `/wp-admin/admin.php?page=wpi-settings`，点击「检查更新」，触发 WP 原生 1-click 热更新。
     - **自主升级方式 B（上传覆盖）**：在 `/wp-admin/plugin-install.php?tab=upload` 直接上传最新的 `main.zip` 并选择「覆盖现有插件」。
   - 确认版本为 `v1.4.5+`（具备胶囊条输入即提交、Contact details saved 定心丸通知、步骤指示器与折叠预设抽屉）。
---

### Step 2：从当前站点动态提取定制要素（拒绝死板套用）

严禁使用死板的假预设或套模子。直接在目标站点控制台或通过 CDP 读取该站的真实商品数据：

```javascript
const siteData = await (async () => {
  const cats = await (await fetch("/wp-json/wp/v2/product_cat?per_page=50")).json();
  const prods = await (await fetch("/wp-json/wp/v2/product?per_page=30")).json();
  return {
    categories: cats.map(c => c.name),
    productTitles: prods.slice(0, 15).map(p => p.title?.rendered)
  };
})();
console.log("站点真实产品线:", siteData);
```

**动态提炼 3~5 个工程核心变量**：
直接观察抓取出的产品标题与分类名称，提炼出销售与工程师给买家出图纸和报价必须获知的几个核心问题：
1. **产品形态/安装方式**（如立式/壁挂/嵌入式、还是特定结构）；
2. **关键规格/选配模块**（根据产品标题中反复出现的关键词提取，如外设开孔、光学参数、材质型号等）；
3. **尺寸/配置参数**（主流对角线尺寸、处理芯片或功率等级）；
4. **采购批量与交期**（样机、小批、批量）。

每个问题直接从该站产品线提取 3~4 个真实典型选项供一键点击，外加自由输入占位符。
---

### Step 3：构建标准的 7 步发散追问表单结构

以分析出的要素为核心，生成符合 `wp-inquiry-chatbot` 规范的标准 7 步 JSON（必须严格包含 `id`, `question`, `type`, `options`, `placeholder`, `require_at_email` 等字段）：

```json
[
  {
    "id": "name_email",
    "question": "Hello, may I have your Name and Business Email to send the CAD drawings and factory quotation?",
    "type": "name_email",
    "required": true,
    "require_at_email": true,
    "prompt_error": "Please provide a valid business email containing \"@\" (e.g. name@company.com) so we can send the quotation.",
    "placeholder": "Your Name & Business Email with @..."
  },
  {
    "id": "whatsapp_contact",
    "question": "Do you use WhatsApp or WeChat for quick engineering review and quotation?",
    "type": "optional_text",
    "options": [
      "📲 Chat on WhatsApp (+8618098949445)",
      "Prefer Email only ✉️"
    ],
    "required": false,
    "placeholder": "Enter your WhatsApp/Phone or select above..."
  },
  {
    "id": "<core_form_factor>",
    "question": "<业务形态与安装方式提问>",
    "type": "choice_or_text",
    "options": ["选项1", "选项2", "选项3", "选项4"],
    "required": false,
    "placeholder": "Select above or describe your enclosure requirements..."
  },
  {
    "id": "<core_peripherals_or_specs>",
    "question": "<核心工程外设/功能配置提问>",
    "type": "choice_or_text",
    "options": ["选项1", "选项2", "选项3", "选项4"],
    "required": false,
    "placeholder": "Select above or list required devices..."
  },
  {
    "id": "<screen_or_dimension>",
    "question": "<尺寸/规格/功率提问>",
    "type": "choice_or_text",
    "options": ["选项1", "选项2", "选项3", "选项4"],
    "required": false,
    "placeholder": "Preferred size or paste a reference model link..."
  },
  {
    "id": "<system_or_driver>",
    "question": "<系统/驱动/芯片/协议配置提问>",
    "type": "choice_or_text",
    "options": ["选项1", "选项2", "选项3", "选项4"],
    "required": false,
    "placeholder": "Specify OS, CPU, RAM or driver requirements..."
  },
  {
    "id": "project_volume",
    "question": "What is your estimated order quantity and project schedule?",
    "type": "choice_or_text",
    "options": [
      "Prototype / 1-2 units sample test",
      "Trial batch (10 - 50 units)",
      "Roll-out / Mass production (> 100 units)",
      "Government / Enterprise Tender stage"
    ],
    "required": false,
    "placeholder": "e.g. 50 units needed in 2 months..."
  }
]
```

---

### Step 4：飞书 Webhook 交互确认与测试连通

1. **检查与用户提问门禁**：
   - 检查 skill 调用参数或当前后台（`/wp-admin/admin.php?page=wpi-settings`）中的 `wpi_webhook_url` 是否已存在。
   - 若未提供且后台为空，**必须使用 `ask` 工具主动向用户确认**：
     > “请问是否需要为此站点配置飞书实时询盘推送机器人？请提供飞书 Webhook 机器人地址（选填密钥）。如暂不配置飞书通知，请输入 'skip' 跳过。”
2. **自动化写入配置**：
   - 访问 `/wp-admin/admin.php?page=wpi-settings`，填入 `wpi_webhook_url` 与 `wpi_webhook_secret` 并提交保存。
3. **连通性实测验证**：
   - 在设置页面点击「发送测试工单卡片至飞书群」（`#wpi-test-webhook-btn`）。
   - 确认返回 `{"success": true}` 并在飞书群收到带有该站域名标签的测试互动卡片。

---

### Step 5：注入表单并固化为站点专属默认预设（关键防丢机制）

为防止后续日常运营或管理员误点“恢复预设”将定制语料覆盖，必须执行**双层固化**：

1. **表单注入并保存（生效到当前运行时）**：
   - 访问 `/wp-admin/admin.php?page=wpi-form-builder`。
   - 在 `wpi_import_corpus_json` 文本框注入 `{"form_questions": [...]}`。
   - 提交表单保存，验证 7 步卡片完整呈现。
2. **固化为站点专属默认预设（Snapshot Persistent）**：
   - 访问 `/wp-admin/admin.php?page=wpi-settings`。
   - 触发内置安全链接：`admin.php?page=wpi-settings&action=save_custom_default&_wpnonce=...`。
   - 确认后台提示：“已成功将当前调整好的追问表单流程与 FAQ 规则保存为「站点专属默认预设」！”
   - 此时 `wpi_custom_default_questions` 将永久锁定该 7 步表单。若未来发生误改，点击「🔄 恢复为自定义默认预设」即可 1 秒还原。

---

### Step 6：清除整站缓存与前台端到端实机验证

1. **清除全站缓存**：
   - WP Super Cache：访问 `/wp-admin/options-general.php?page=wpsupercache` 点击「删除缓存」。
   - 其他缓存插件（Seraphinite / WP Rocket / LiteSpeed）：执行对应 Purge All 操作。
2. **前台商品单品页实测**：
   - 浏览器打开任意代表性产品单品页（如 `/product/xxx/`）。
   - 检查浮窗标题与上下文横幅：是否精准捕获 `CURRENTLY VIEWING: <Product Title>`。
   - 模拟填表交互：
     - 输入有效邮箱 `test@company.com`，点击发送。
     - 确认前端顺利流转至 Step 2 WhatsApp 即时选项。
     - 确认 Step 3~7 选项按钮均可直接点选，且选项文本与站点业务高度贴合。
3. **后台工单与飞书核验**：
   - 检查 `/wp-admin/admin.php?page=wpi-leads` 是否成功生成新工单。
   - 检查工单的 `payload_json` 是否规范格式化包含所选的各步规格参数。
