# 改进计划与变更记录 (CHANGES)

本项目基于开源项目 [angkosal/laravel-pos](https://github.com/angkosal/laravel-pos) 进行 A 路线二次开发与功能增强（版本定位：laravel-pos-plus，PHP 8.2+ / Laravel 12）。

---

## 改进计划与实施（5 项实质改进）

### 1. [新功能] POS 销售小票打印与 PDF 导出系统 (Order Receipt & Thermal Printing)
- **背景与痛点**：原项目进货模块虽然有 80mm 进货单，但前台核心 POS 收银模块长期缺少订单销售小票与收据导出功能（原项目 README 中标记为待办 `[ ] Print or download sale receipt`）。收银员结算后无法打印凭据给顾客，也无法生成电子凭单。
- **实施内容**：
  - 新增订单热敏打印页面 (`/admin/orders/{order}/print`)，针对 80mm/58mm 热敏纸张适配排版，支持在结算完成或查看详情时直接唤起系统打印；支持 `autoprint=1` 参数自动唤起浏览器打印对话框。
  - 新增基于 Dompdf 的 80mm PDF 格式标准销售小票流式查看与下载路由 (`/admin/orders/{order}/receipt`)。
  - 小票包含完整商业凭证信息：店铺名称、简介、小票单号、出票时间、收银员、客户姓名、商品明细清单（品名、数量、单价、小计）、小计金额、实收金额、找零/未结清余额、付款状态徽章与致谢底部署名。
  - 在 POS 收银台 (`Cart.jsx`) 结算完成后提供一键「打印小票」弹窗交互；在订单管理列表 (`orders/index.blade.php`) 每行新增「打印小票」与「下载 PDF 小票」快捷按钮，并在发票详情弹窗底部集成打印入口。

### 2. [新功能] 销售数据分析报表中心与 CSV 导出 (Sales Reports & Analytics with Export)
- **背景与痛点**：原项目仪表盘仅有今日简要数据卡片，缺乏独立的销售统计与报表分析系统，商户无法按周期回顾历史销售趋势、客单价及单品毛利。
- **实施内容**：
  - 新增报表控制器 `App\Http\Controllers\Report\ReportController`，对应路由 `/admin/reports`。
  - 支持快捷周期筛选（今天、近 7 天、本月、上月、全部时间）以及任意起止日期的自定义筛选。
  - 汇总关键经营指标：订单总数 (Total Orders)、销售总额 (Total Revenue)、实收金额 (Total Received)、售出商品件数 (Total Items Sold)、平均客单价 (AOV)、毛利估算 (Gross Profit = 售价 - 进价成本)。
  - 提供按日统计的每日销售额与订单量明细表，以及周期内热销商品排行榜（销量与销售额）。
  - 支持将指定筛选周期内的销售报表一键导出为标准 CSV 表格 (`/admin/reports/export`)，自动附加 UTF-8 BOM 兼容 Excel。并在侧边导航栏增加「Reports」导航项。

### 3. [功能优化] 动态库存预警机制与缺货补货建议 (Dynamic Stock Warning & Low Stock Center)
- **背景与痛点**：原项目虽然在系统设置里保存了 `warning_quantity`（库存预警数），但在 `ProductScopes` 源码中全部硬编码为 `< 10`，导致商家自定义设置无效；商品管理页面也缺少针对低库存与售罄商品的筛选。
- **实施内容**：
  - 改造 `ProductScopes::isLowStock(?int $threshold = null)` 与 `scopeLowStock($query, ?int $threshold = null)`，支持动态读取系统配置 `config('settings.warning_quantity', 10)`，并支持自定义传参覆盖。
  - 新增 `scopeOutOfStock`，准确判定库存归零或负库存情况。
  - 在商品管理列表 (`/admin/products`) 顶部增加「库存预警 (Low Stock)」与「缺货售罄 (Out of Stock)」一键筛选标签，表格中对低库存与零库存商品分别展示警告与危险徽章。
  - 在报表中心设立「库存预警与补货建议」专区，罗列待补货商品明细并提供一键跳转至创建进货单 (`purchases.create`)。

### 4. [功能优化] 多币种适配、符号位置与全局货币格式化引擎 (Multi-Currency & Global Formatting)
- **背景与痛点**：原项目货币符号仅作为单一文本随意拼接，未考虑国际化场景下不同货币符号位置（如部分币种符号位于数值后，如 `100 ៛`）、小数位数及控制台运行无配置加载时的容错。
- **实施内容**：
  - 扩展系统设置 (`/admin/settings`)，增加常用货币预设下拉选择（USD `$`, EUR `€`, GBP `£`, CNY/JPY `¥`, KHR `៛`, INR `₹` 等）并支持自定义货币符号。
  - 增加货币符号位置选项 (`currency_placement`：前置 `$ 10.00` 或 后置 `10.00 $` / `10.00 ៛`)。
  - 在 `app/helpers.php` 中新增全局辅助函数 `currency_symbol()` 与 `currency_format($amount, int $decimals = 2)`，全站（仪表盘、商品管理、购物车结算、小票、报表中心）统一调用，支持灵活格式化并具备优雅降级。

### 5. [缺陷修复] 订单列表全局统计准确性修复与兼容性优化 (Order Summary & Query Fixes)
- **背景与痛点**：原项目 `OrderController@index` 中统计的总金额 `$total` 与实收金额 `$receivedAmount` 是直接对当前分页后的集合 (`paginate(10)`) 执行 `sum()`，当订单超过 10 条产生分页时，底部汇总行仅统计了当前页而不是全部符合条件的订单总额，导致财务汇总严重失真。
- **实施内容**：
  - 修复 `OrderController@index`：在执行分页之前，先基于克隆的基础 Query 计算当前筛选条件下的全量总金额与实收金额，再执行分页输出，确保在多页分页时总统计数据真实准确。
  - 规范相关商品统计作用域的字段聚合与分组，提升 SQLite 与 MySQL 跨数据库环境的兼容性。

---

## 自测记录

- **自测时间**：2026-11-17
- **测试环境**：Ubuntu 24.04 LTS, PHP 8.3.6 (cli), Composer 2.7.1, Node.js v24.22.1, SQLite 3.45.1

### 1. 依赖安装与构建
```bash
# 后端依赖安装
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction
# 结果：145 个依赖包全部安装完成，自动执行 package:discover

# 前端依赖安装与生产打包
npm install
npm run build
# 结果：Vite 成功编译打包 resources/js/app.js 与 resources/sass/app.scss，生成 public/build 资源
```

### 2. 数据库迁移与数据填充
```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=ProductSeeder
php artisan storage:link
# 结果：20 个迁移全部执行成功，默认管理员、设置、示例客户与产品数据填充成功，存储链接创建正常
```

### 3. 自动化测试套件 (Pest)
```bash
./vendor/bin/pest
# 结果：
# Tests:    242 passed (720 assertions)
# Duration: 8.04s
# 覆盖范围：包含原项目全部 230 个测试，以及本次新增的 12 个针对小票打印、销售报表、多币种设置与库存预警的自动化测试，全部通过且 0 失败。
```

### 4. 核心功能及 HTTP 流程自测
启动内置服务器并进行端到端链路测试：
```bash
php artisan serve --port=8000 --host=127.0.0.1
```
测试结果：
- `GET /`：HTTP 302 重定向至 `/admin`。
- `GET /login`：HTTP 200，登录表单正常渲染。
- `POST /login` (admin@gmail.com / admin123)：HTTP 302 成功通过认证并写入 Session。
- `GET /admin` (仪表盘)：HTTP 200，收入与今日收入正常按照 `currency_format` 展示。
- `GET /admin/products`：HTTP 200，商品列表展示正常；`?stock_status=low_stock` 与 `?stock_status=out_of_stock` 筛选准确，库存预警徽章正常标红/标黄。
- `GET /admin/cart`：HTTP 200，POS 界面正常加载。
- `GET /admin/orders`：HTTP 200，订单列表统计准确，每行均展示查看发票、打印小票与 PDF 下载按钮。
- `GET /admin/orders/1/print`：HTTP 200，80mm 热敏打印预览正常，样式布局完整。
- `GET /admin/orders/1/receipt`：HTTP 200，Content-Type 为 `application/pdf`，成功输出 PDF 小票二进制流。
- `GET /admin/reports`：HTTP 200，成功呈现销售汇总 KPI、每日销售趋势明细表、热销商品榜单与低库存补货提醒。
- `GET /admin/reports/export`：HTTP 200，Content-Type 为 `text/csv; charset=utf-8`，成功流式导出带有 UTF-8 BOM 的销售明细表。
- `GET /admin/settings`：HTTP 200，成功加载货币预设选择器与符号位置配置。
