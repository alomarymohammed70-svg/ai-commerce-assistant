# AI Commerce Assistant - Backend API

> **منصة ذكية لأبحاث التجارة الإلكترونية وتحليل الجدوى والمنتجات الخارجية**  
> *Product Research + Market Intelligence + AI Commerce Assistant*

---

## 📖 نظرة عامة على المشروع (Project Overview)

**AI Commerce Assistant** هو نظام Backend متكامل مبني بواسطة **Laravel 13** و **PHP 8.5** يمثل النواة الذكية لعمليات أبحاث التجارة الإلكترونية وتحليل الجدوى للمنتجات من مصادر خارجية متعددة (مثل Amazon, Alibaba, AliExpress, eBay).

المشروع **ليس متجرًا إلكترونيًا** ولا نظام إدارة مخزون محلي، بل منصة ذكاء سوقي (Market Intelligence Engine) تُمكّن رواد الأعمال من تقديم استفسارات ومحددات وميزانيات (مثل: *"لدي 500 دولار وأريد منتجاً رابحاً للتجارة الإلكترونية"*). يقوم النظام بجدولة واسترجاع بيانات المنتجات الخارجية، توحيدها (Normalization)، قياس مؤشرات العرض والطلب وحجم المنافسة وهوامش الربح المتوقعة، وتهيئتها لمحركات الذكاء الاصطناعي لتقديم تحليلات وتوصيات دقيقة.

---

## 🏗️ مراحل التطوير (Development Roadmap)

- [x] **المرحلة الأولى:** تأسيس الـ Backend، الاتصال بقاعدة بيانات XAMPP MariaDB، معمارية الـ API Versioning `/api/v1/`، نظام المصادقة بـ Laravel Sanctum، وتوحيد الاستجابات `ApiResponseTrait`.
- [x] **المرحلة الثانية:** بناء نواة أبحاث المنتجات (Product Research Core):
  - نطاق المنتجات (Product Domain) وتصنيفاتها ومصادرها.
  - طبقة التوحيد القياسي للمصادر الخارجية (Normalization Layer).
  - محرك التحليل المالي الحسابي (Deterministic Analysis Engine).
  - ضبط الدقة المالية ومنع أخطاء الفاصلة العائمة (BCMath Precision).
  - واجهات الـ REST API للتصفية المتقدمة والتفاصيل والتحليل.
- [ ] **المرحلة الثالثة (المستقبلية):** جلسات البحث ومحرك الذكاء الاصطناعي (AI Analysis Layer).
- [ ] **المرحلة الرابعة (المستقبلية):** الربط الحي بالمصادر الخارجية والـ Webhooks.
- [ ] **المرحلة الخامسة (المستقبلية):** تطبيق الهاتف المحمول (Flutter Mobile App).

---

## 🛠️ متطلبات التشغيل (System Requirements)

- **نظام التشغيل:** Windows 10/11 (أو Linux / macOS)
- **PHP:** `^8.5` (الإصدار الفعلي: `PHP 8.5.12`)
- **Composer:** `^2.11` (الإصدار الفعلي: `Composer 2.11.1`)
- **خادم قواعد البيانات:** MySQL / MariaDB (عبر XAMPP - الإصدار الفعلي: `MariaDB 10.11.16`)
- **إضافات PHP المطلوبة (PHP Extensions):**
  - `pdo_mysql` (ضروري للاتصال بقاعدة بيانات XAMPP)
  - `bcmath` (ضروري للحسابات المالية الدقيقة والنسب وهوامش الربح)
  - `mysqli`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip`

---

## 🗄️ المخطط الهيكلي لقاعدة البيانات (Database Architecture - Phase 2)

```text
product_sources (المصادر الخارجية)
       │ 1
       │
       │ N
products (المنتجات الموحدة) ────────── N : 1 ────────── categories (التصنيفات الهرمية)
       │ 1                                                     │
       │                                                       └── (parent_id) ──┐
       │ N                                                                       │ (Tree)
product_metrics (سجل التحليلات والمؤشرات) <───────────────────────────────────────┘
```

### تفاصيل الجداول:
1. **`product_sources`:**
   - `id`, `code` (Unique: 'amazon', 'alibaba', 'aliexpress', 'ebay'), `name`, `base_url`, `is_active`, `timestamps`.
2. **`categories`:**
   - `id`, `name`, `slug` (Unique), `description`, `parent_id` (Self-referencing Foreign Key للأقسام الفرعية), `is_active`, `timestamps`.
3. **`products`:**
   - `id`, `source_id` (FK), `category_id` (FK Nullable), `external_id` (Unique per source), `title`, `description`, `brand`, `url`, `image_url`, `currency` (3 Chars), `price` (DECIMAL 12,4), `original_price` (DECIMAL 12,4 Nullable), `rating` (DECIMAL 3,2), `reviews_count` (Unsigned Int), `is_available` (Bool), `raw_payload` (JSON), `timestamps`.
4. **`product_metrics`:**
   - `id`, `product_id` (FK), `selling_price` (12,4), `purchase_price` (12,4), `shipping_cost` (12,4), `estimated_fees` (12,4), `other_costs` (12,4), `estimated_profit` (12,4), `profit_margin` (7,4 %), `return_on_investment` (8,4 %), `opportunity_score` (5,2: 0-100), `risk_score` (5,2: 0-100), `competition_level` ('low', 'medium', 'high'), `calculation_notes` (JSON), `calculated_at` (Timestamp), `timestamps`.

---

## 🔄 طبقة التوحيد القياسي للمصادر (Product Normalization Layer)

تستقبل المنصة البيانات الخام (Raw Payloads) من مصادر تختلف جذرياً في بنيتها، وتقوم بتحويلها إلى كائن بيانات موحد `ExternalProductDto`:

| المصدر | الحقول الأصلية (Raw Source Data) | الحقل الموحد في النظام (Normalized Field) |
| :--- | :--- | :--- |
| **Amazon** | `asin`, `product_title`, `buying_price`, `stars`, `ratings_total` | `external_id`, `title`, `price`, `rating`, `reviews_count` |
| **Alibaba** | `product_id`, `subject`, `fob_price`, `supplier_rating`, `moq` | `external_id`, `title`, `price`, `rating`, `min_order_quantity` |
| **AliExpress**| `item_id`, `title`, `target_sale_price`, `evaluate_rate` (96%) | `external_id`, `title`, `price`, `rating` (4.8 / 5) |
| **eBay** | `item_id`, `title`, `current_bid_price`, `feedback_score` | `external_id`, `title`, `price`, `reviews_count` |

---

## 🧮 محرك التحليل المالي الحتمي (Deterministic Analysis Engine)

لا يعتمد محرك التحليل في هذه المرحلة على أي تخمين أو ذكاء اصطناعي، بل على خوارزميات حسابية حتمية دقيقة باستخدام مكتبة **BCMath**:

1. **إجمالي التكلفة (Total Cost):**
   $$\text{Total Cost} = \text{Purchase Price} + \text{Shipping Cost} + \text{Estimated Fees} + \text{Other Costs}$$
2. **صافي الربح المتوقع (Estimated Net Profit):**
   $$\text{Estimated Profit} = \text{Selling Price} - \text{Total Cost}$$
3. **هامش الربح (Profit Margin %):**
   $$\text{Profit Margin} = \left(\frac{\text{Estimated Profit}}{\text{Selling Price}}\right) \times 100 \quad (\text{if Selling Price} > 0)$$
4. **العائد على الاستثمار (ROI %):**
   $$\text{ROI} = \left(\frac{\text{Estimated Profit}}{\text{Total Cost}}\right) \times 100 \quad (\text{if Total Cost} > 0)$$
5. **مؤشر الفرصة (Opportunity Score: 0 - 100):**
   حساب وزني معياري يدمج: نسبة هامش الربح (حتى 40 نقطة)، تقييم المنتج (حتى 30 نقطة)، حجم المبيعات والطلبات (حتى 20 نقطة)، وملاءمة رأس المال المبدئي (حتى 10 نقاط)، مع فرض عقوبة صارمة في حال الخسارة.
6. **مؤشر المخاطر (Risk Score: 0 - 100):**
   حساب وزني يرصد: هوامش الربح الضئيلة أو السالبة، تدني التقييمات، ضعف تاريخ المبيعات، وارتفاع كلفة الشراء المبدئية.

---

## 📡 واجهات البرمجة (API Endpoints - Version 1)

جميع الواجهات مسجلة تحت بادئة `/api/v1/`:

| Method | Endpoint | الوصف | الحماية (Auth) | معايير الحماية والتصفية |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | فحص جاهزية الخدمة وحالة النظام | عام (Public) | - |
| `POST` | `/api/v1/auth/register` | تسجيل مستخدم جديد وإصدار التوكن | عام (Public) | Rate Limit: 10 req/min |
| `POST` | `/api/v1/auth/login` | تسجيل الدخول وإصدار التوكن | عام (Public) | Rate Limit: 10 req/min |
| `GET` | `/api/v1/auth/me` | جلب الملف الشخصي للمستخدم الحالي | محمي (`auth:sanctum`) | Bearer Token مطلوب |
| `POST` | `/api/v1/auth/logout` | تسجيل الخروج وإبطال التوكن | محمي (`auth:sanctum`) | Bearer Token مطلوب |
| `GET` | `/api/v1/categories` | استعراض شجرة التصنيفات النشطة | عام (Public) | Rate Limit: 60 req/min |
| `GET` | `/api/v1/sources` | استعراض مصادر المنتجات المعتمدة | عام (Public) | Rate Limit: 60 req/min |
| `GET` | `/api/v1/products` | استعراض المنتجات وتصفيتها مع Pagination | عام (Public) | Whitelist Filtering + Pagination |
| `GET` | `/api/v1/products/{id}` | جلب تفاصيل المنتج والتحليلات السابقة | عام (Public) | Eager Loading لمنع N+1 |
| `POST` | `/api/v1/analysis/calculate` | أداة حساب الأرباح والمؤشرات المباشرة | عام (Public) | حساب مالي فوري حتمي |
| `POST` | `/api/v1/products/{id}/analyze` | احتساب وتخزين سجل تحليلي للمنتج | محمي (`auth:sanctum`) | Bearer Token مطلوب |

### معاملات التصفية المسموحة في `GET /api/v1/products`:
- `source`: فلترة برمز المصدر (مثل: `amazon`, `alibaba`, `aliexpress`, `ebay`).
- `category`: فلترة برمز التصنيف (مثل: `electronics`, `fashion`).
- `min_price` و `max_price`: نطاق السعر.
- `min_rating`: الحد الأدنى للتقييم (0.00 إلى 5.00).
- `currency`: رمز العملة (مثل: `USD`, `EUR`).
- `availability`: التوفر في المخزون (`true` / `false`).
- `search`: بحث نصي آمن في العنوان والعلامة التجارية.
- `sort_by`: حقول الترتيب المسموحة فقط (`price`, `rating`, `reviews_count`, `created_at`, `title`).
- `sort_direction`: اتجاه الترتيب (`asc`, `desc`).
- `per_page`: حجم الصفحة (من 1 إلى 100، الافتراضي 15).

---

## 🧪 منظومة الاختبارات الآلية (Automated Testing)

تشمل حزمة الاختبارات تغطية شاملة لكافة مكونات المرحلة الأولى والثانية:
```bash
cd ai-commerce-assistant
php artisan test
```

### نتيجة الاختبارات الحالية:
```text
Tests: 38 passed (322 assertions)
Status: 100% SUCCESS
```

---

## 🏛️ البنية المعمارية للمجلدات (Architecture Directory Structure)

```text
ai-commerce-assistant/
├── app/
│   ├── Contracts/
│   │   ├── AI/
│   │   │   └── MarketAnalysisEngineInterface.php
│   │   ├── Currency/
│   │   │   └── CurrencyConverterInterface.php         # عقد تحويل العملات
│   │   └── ExternalSources/
│   │       ├── ExternalSourceConnectorInterface.php
│   │       └── ProductNormalizerInterface.php         # عقد توحيد بيانات المصادر
│   ├── DTOs/
│   │   ├── AI/
│   │   │   └── MarketIntelligenceDto.php
│   │   └── ExternalSources/
│   │       └── ExternalProductDto.php                 # كائن نقل البيانات الموحد
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AnalysisController.php                 # وحدة التحكم بالحسابات المباشرة
│   │   │   ├── AuthController.php
│   │   │   ├── CategoryController.php                 # وحدة التحكم بالتصنيفات
│   │   │   ├── ProductController.php                  # وحدة التحكم بالمنتجات والتحليل
│   │   │   └── ProductSourceController.php            # وحدة التحكم بمصادر المنتجات
│   │   ├── Requests/Api/V1/
│   │   │   ├── Analysis/
│   │   │   │   └── StandaloneAnalysisRequest.php
│   │   │   ├── Auth/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── RegisterRequest.php
│   │   │   └── Product/
│   │   │       ├── AnalyzeProductRequest.php
│   │   │       └── ProductFilterRequest.php           # فحص وتأمين معاملات البحث والفلترة
│   │   └── Resources/Api/V1/
│   │       ├── CategoryResource.php
│   │       ├── ProductDetailResource.php              # استجابة تفاصيل المنتج الموسعة
│   │       ├── ProductMetricResource.php
│   │       ├── ProductResource.php                    # استجابة قائمة المنتجات
│   │       ├── ProductSourceResource.php
│   │       └── UserResource.php
│   ├── Models/
│   │   ├── Category.php                               # نموذج التصنيفات الهرمية
│   │   ├── Product.php                                # نموذج المنتجات الموحدة
│   │   ├── ProductMetric.php                          # نموذج سجلات التحليل والجدوى
│   │   ├── ProductSource.php                          # نموذج المصادر الخارجية
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php                     # تسجيل الخدمات والـ Singletons
│   ├── Services/
│   │   ├── Analysis/
│   │   │   └── ProductAnalysisEngine.php              # محرك التحليل المالي الحتمي
│   │   ├── Auth/
│   │   │   └── AuthService.php
│   │   ├── Currency/
│   │   │   └── StaticCurrencyConverter.php            # محول العملات المرجعي بـ BCMath
│   │   ├── ExternalSources/
│   │   │   ├── Connectors/
│   │   │   │   └── AbstractSourceConnector.php
│   │   │   ├── Normalizers/                           # مشغلات التوحيد لكل متجر
│   │   │   │   ├── AlibabaProductNormalizer.php
│   │   │   │   ├── AliExpressProductNormalizer.php
│   │   │   │   ├── AmazonProductNormalizer.php
│   │   │   │   ├── EbayProductNormalizer.php
│   │   │   │   └── GenericProductNormalizer.php
│   │   │   ├── ExternalSourceManager.php
│   │   │   └── ProductNormalizationService.php        # خدمة إدارة مشغلات التوحيد
│   │   └── Product/
│   │       ├── ProductIngestionService.php            # خدمة إدخال وتخزين المنتجات
│   │       └── ProductQueryService.php                # خدمة الاستعلام والفلترة الآمنة
│   └── Traits/
│       └── ApiResponseTrait.php                       # توحيد استجابات الـ JSON والـ Pagination
├── database/
│   ├── factories/                                     # مصانع النماذج للاختبارات
│   ├── migrations/                                    # تهجيرات الجداول والعلاقات
│   └── seeders/                                       # بذور المصادر والأقسام القياسية
└── tests/
    ├── Feature/
    │   ├── Api/V1/
    │   │   ├── AuthApiTest.php
    │   │   └── ProductResearchApiTest.php             # اختبارات الـ API الشاملة
    │   └── ProductIngestionTest.php                   # اختبارات دورة إدخال المنتجات
    └── Unit/
        ├── ExternalSourcesArchitectureTest.php
        ├── FinancialAnalysisEngineTest.php            # اختبارات دقة الحسابات المالية
        └── ProductNormalizationTest.php               # اختبارات توحيد بيانات المتاجر
```
