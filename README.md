# laravel-pos-plus

**laravel-pos-plus** 是基于 Laravel 12 与 React 构建的现代化 POS 桌面/收银台与进销存管理系统增强版，集成了扫码收银、80mm 热敏销售小票打印与 PDF 导出、多币种自适应引擎、动态库存预警机制及销售数据分析报表中心。

**laravel-pos-plus** is an enhanced, modern Point of Sale (POS) and inventory management system built with Laravel 12 and React, featuring barcode scanning checkout, 80mm thermal receipt printing and PDF export, adaptive multi-currency formatting, dynamic stock alerts, and a sales analytics reporting center.

---

## 中文文档

### 功能与改进点

本项目在原有系统基础上完成了以下 5 项实质性增强与缺陷修复（对应 [CHANGES.md](CHANGES.md)）：

1. **POS 销售小票打印与 PDF 导出 (Order Receipt & Thermal Printing)**
   - 补齐了原项目长期未实现的销售小票功能（原项目待办 `Print or download sale receipt`）。
   - 提供 80mm/58mm 标准热敏纸张 HTML 打印视图 (`/admin/orders/{order}/print`)，支持结算完成或发票查看时自动唤起系统打印对话框。
   - 提供基于 Dompdf 引擎生成的 80mm PDF 格式销售凭单流式查看与下载 (`/admin/orders/{order}/receipt`)。
   - 小票包含商户信息、小票单号、收银员、客户、商品明细清单（单价/数量/小计）、实收、找零与支付状态。并在 POS 收银台与订单列表提供一键打印入口。

2. **销售数据分析报表中心与 CSV 导出 (Sales Reports & Analytics with Export)**
   - 新增独立的销售与库存报表模块 (`/admin/reports`)，支持按今天、近 7 天、本月、上月及自定义起止日期筛选。
   - 汇总关键经营指标面板：销售总额 (Total Revenue)、订单总数 (Orders)、售出件数 (Items Sold)、客单价 (AOV)、预估毛利 (Gross Profit)。
   - 提供每日销售额/订单量明细趋势表、周期内热销商品榜单以及一键导出 CSV 表格 (`/admin/reports/export`)。

3. **动态库存预警机制与缺货补货建议 (Dynamic Stock Warning & Low Stock Center)**
   - 修复底层硬编码预警阈值的缺陷，联动系统设置中的 `warning_quantity`，支持商户自定义预警线。
   - 在商品管理列表 (`/admin/products`) 增加「库存预警 (Low Stock)」与「缺货售罄 (Out of Stock)」快捷筛选标签与状态徽章。
   - 在报表中心设立低库存商品补货预警，支持一键直达发起采购进货单 (`purchases.create`)。

4. **多币种适配、符号位置与全局货币格式化引擎 (Multi-Currency & Global Formatting)**
   - 升级系统设置 (`/admin/settings`)，内置常见主流货币预设（美元 USD `$`, 欧元 EUR `€`, 英镑 GBP `£`, 人民币/日元 CNY/JPY `¥`, 柬埔寨瑞尔 KHR `៛`, 印度卢比 INR `₹` 等）并支持自定义货币符号。
   - 支持货币符号位置配置（前置如 `$ 100.00`，后置如 `100.00 $` 或 `100.00 ៛`）。
   - 全局辅助函数 `currency_format($amount)` 与 `currency_symbol()` 统一全站所有前后台界面的金额渲染，并具备环境容错与优雅降级。

5. **订单列表全局统计准确性修复与兼容性优化 (Order Summary & Query Fixes)**
   - 修复了原项目中 `OrderController@index` 分页时仅对当页 10 条数据求和的缺陷，改为统计当前筛选条件下的全量总额与实收金额，确保多页分页时财务汇总真实准确。
   - 优化了聚合查询语句，保障在跨数据库严格模式下的稳定运行。

---

### 快速开始

#### 1. 环境准备
- PHP >= 8.2（需开启 `pdo_sqlite` 或 `pdo_mysql`, `mbstring`, `curl`, `gd`, `zip`, `xml` 扩展）
- Composer >= 2.0
- Node.js >= 18.0 & npm >= 9.0
- SQLite 或 MySQL

#### 2. 安装与配置
克隆代码仓库并进入项目根目录：
```bash
git clone <repository-url> laravel-pos-plus
cd laravel-pos-plus
```

安装 PHP 依赖：
```bash
composer install
```

复制环境配置文件并生成应用密钥：
```bash
cp .env.example .env
php artisan key:generate
```

根据实际环境配置 `.env` 中的数据库连接（默认使用 SQLite 无需额外安装数据库服务）：
```ini
DB_CONNECTION=sqlite
# 如使用 MySQL，填写对应配置占位符：
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database_name
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

#### 3. 初始化数据库与示例数据
若使用 SQLite，先创建数据库空文件：
```bash
touch database/database.sqlite
```

执行数据库迁移与种子数据填充：
```bash
php artisan migrate
php artisan db:seed
```
> 初始化后默认管理员账号为：
> - 邮箱：`admin@gmail.com`
> - 密码：`admin123`

可选填充示例客户与商品数据：
```bash
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=ProductSeeder
```

创建软链接以便上传商品图片与访问静态资源：
```bash
php artisan storage:link
```

#### 4. 前端资源编译
安装前端依赖并编译生产静态资源：
```bash
npm install
npm run build
```

#### 5. 启动服务与访问
启动 Laravel 开发服务器：
```bash
php artisan serve
```
在浏览器中打开 `http://127.0.0.1:8000`，系统将自动跳转至后台登录页面，输入管理员账号密码即可登录使用。

#### 6. 运行自动化测试
执行自动化测试套件：
```bash
./vendor/bin/pest
```
（包含 242 项测试用例，涵盖认证、购物车、商品、订单、收据打印、报表分析及多币种设置，测试全部通过）。

---

### 来源与致谢

本项目基于 [laravel-pos](https://github.com/angkosal/laravel-pos) 二次开发，原项目由 angkosal 以 MIT 许可证开源。

---

### 许可证

本项目沿用原项目的开源许可证，以 MIT 许可证发布。全文详见根目录 [LICENSE](LICENSE)。

---

## English Documentation

### Features & Enhancements

This project introduces 5 substantial improvements and bug fixes over the original codebase (see [CHANGES.md](CHANGES.md) for full technical details):

1. **POS Sale Receipt Printing & PDF Export**
   - Implemented thermal receipt printing for sales orders (addressing the long-standing upstream roadmap item `Print or download sale receipt`).
   - Standard 80mm/58mm printable HTML view (`/admin/orders/{order}/print`) with auto-print dialog support.
   - Streamed 80mm PDF download powered by Dompdf (`/admin/orders/{order}/receipt`).
   - Detailed breakdown includes store metadata, receipt sequence, cashier, customer, line items (unit price, quantity, subtotal), total, payments, change due, and payment status badges.

2. **Sales Analytics Reports & CSV Export**
   - Dedicated business reporting dashboard (`/admin/reports`) with date range filtering (Today, Last 7 Days, This Month, Last Month, and Custom range).
   - High-level KPIs: Total Revenue, Total Orders, Items Sold, Average Order Value (AOV), and Estimated Gross Profit.
   - Daily sales trend table and Top Selling Products ranking.
   - One-click CSV export (`/admin/reports/export`) with UTF-8 BOM encoding for Excel compatibility.

3. **Dynamic Low Stock Alerts & Replenishment Center**
   - Fixed hardcoded threshold issues by binding stock warning checks dynamically to `config('settings.warning_quantity')`.
   - Added quick stock filters on the Products list (`/admin/products?stock_status=low_stock` & `out_of_stock`) with color-coded warning badges.
   - Dedicated low-stock alert section in reports with direct shortcuts to create purchase orders (`purchases.create`).

4. **Adaptive Multi-Currency Support & Global Formatter**
   - Upgraded store settings (`/admin/settings`) with currency presets (USD `$`, EUR `€`, GBP `£`, CNY/JPY `¥`, KHR `៛`, INR `₹`, etc.) and custom symbols.
   - Configurable currency placement (Prefix `$ 100.00` or Suffix `100.00 $` / `100.00 ៛`).
   - Universal `currency_format($amount)` helper ensuring uniform rendering across dashboard, products, cart, receipts, and reports with graceful fallbacks.

5. **Order Financial Summary Fix & Query Optimization**
   - Fixed a calculation issue where `OrderController@index` summed only the current page (10 records) instead of the entire filtered query.
   - Normalized SQL group aggregations for smooth cross-database compatibility (MySQL & SQLite).

---

### Quick Start

#### 1. Prerequisites
- PHP >= 8.2 (extensions: `pdo_sqlite` or `pdo_mysql`, `mbstring`, `curl`, `gd`, `zip`, `xml`)
- Composer >= 2.0
- Node.js >= 18.0 & npm >= 9.0
- SQLite or MySQL

#### 2. Installation & Setup
Clone the repository:
```bash
git clone <repository-url> laravel-pos-plus
cd laravel-pos-plus
```

Install PHP dependencies:
```bash
composer install
```

Configure environment file:
```bash
cp .env.example .env
php artisan key:generate
```

Configure database settings in `.env` (SQLite is configured by default):
```ini
DB_CONNECTION=sqlite
# Or configure MySQL credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database_name
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

#### 3. Database Migration & Seeding
Create SQLite database file if using SQLite:
```bash
touch database/database.sqlite
```

Run migrations and seed default administrative account:
```bash
php artisan migrate
php artisan db:seed
```
> Default administrator credentials:
> - Email: `admin@gmail.com`
> - Password: `admin123`

Optional demo seeders for customers and products:
```bash
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=ProductSeeder
```

Link public storage directory:
```bash
php artisan storage:link
```

#### 4. Frontend Asset Compilation
```bash
npm install
npm run build
```

#### 5. Run Server
```bash
php artisan serve
```
Open `http://127.0.0.1:8000` in your browser.

#### 6. Run Test Suite
```bash
./vendor/bin/pest
```
All 242 tests pass with 720 assertions.

---

### Source & Acknowledgements

This project is a secondary development based on [laravel-pos](https://github.com/angkosal/laravel-pos), originally open-sourced by angkosal under the MIT License.

---

### License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
