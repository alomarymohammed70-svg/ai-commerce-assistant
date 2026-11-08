# AI Commerce Assistant - Backend API

> **منصة ذكية لأبحاث التجارة الإلكترونية وتحليل الجدوى والمنتجات الخارجية**  
> *Product Research + Market Intelligence + AI Commerce Assistant*

---

## 📖 نظرة عامة على المشروع (Project Overview)

**AI Commerce Assistant** هو نظام Backend متكامل مبني بواسطة **Laravel 13** و **PHP 8.5** يمثل النواة الذكية لعمليات أبحاث التجارة الإلكترونية وتحليل الجدوى ومقارنة الأسعار بين الأسواق العالمية (مثل eBay, Amazon, Alibaba).

المشروع **ليس متجرًا إلكترونيًا** ولا نظام إدارة مخزون محلي، بل منصة ذكاء سوقي (Market Intelligence Engine) تُمكّن رواد الأعمال من البحث في الأسواق الخارجية، استرجاع عروض الأسعار الحية، توحيدها قياسياً (Normalization)، مطابقتها هندسياً مع المنتجات المرجعية (Canonical Products)، قياس ومقارنة الأسعار وهوامش الربح بين الأسواق، مع تطبيق أعلى معايير الأمان وتجنب الحسابات الخاطئة.

---

## 🏗️ مراحل التطوير (Development Roadmap)

- [x] **المرحلة الأولى:** تأسيس الـ Backend، الاتصال بقاعدة بيانات XAMPP MariaDB، معمارية الـ API Versioning `/api/v1/`، نظام المصادقة بـ Laravel Sanctum، وتوحيد الاستجابات `ApiResponseTrait`.
- [x] **المرحلة الثانية:** بناء نواة أبحاث المنتجات (Product Research Core):
  - نطاق المنتجات (Product Domain) وتصنيفاتها ومصادرها.
  - طبقة التوحيد القياسي للمصادر الخارجية (Normalization Layer).
  - محرك التحليل المالي الحسابي (Deterministic Analysis Engine).
  - ضبط الدقة المالية ومنع أخطاء الفاصلة العائمة (BCMath Precision).
  - واجهات الـ REST API للتصفية المتقدمة والتفاصيل والتحليل.
- [x] **المرحلة الثالثة:** أبحاث الأسواق المتعددة وعروض الأسعار (Multi-Market Research & Product Offers):
  - معمارية المنتج المرجعي وعروض الأسواق: `CanonicalProduct` $\rightarrow$ `ProductListing`.
  - محرك المطابقة الاستدلالي الحتمي (Product Matching Engine) بدون AI.
  - محرك مقارنة الأسعار وحساب الفروقات السوقية (Price Comparison Engine).
  - معالجة أمان العملات (Currency Safety) ومنع الحساب المالي العشوائي بين عملات مختلفة.
  - نمط الإستراتيجية لمؤشر الفرص (Opportunity Score Strategy Engine).
- [x] **المرحلة الرابعة:** تكامل بيانات السوق الحقيقية — eBay Browse API (External Market Data Integration):
  - واجهة الموصل العام للأسواق (`MarketConnectorInterface`).
  - موصل eBay الحقيقي (`EbayConnector`) للاتصال بـ eBay Browse API.
  - إدارة توثيق OAuth 2.0 Client Credentials والتخزين المؤقت للتوكن (`EbayAuthService`).
  - موصل الاختبارات الآمنة دون إنترنت (`MockEbayConnector`).
  - معالجة الأخطاء والمهلات الزمنية وتجاوز المعدل (`Rate Limiting & Backoff`).
  - التخزين المؤقت الذكي لنتائج البحث وتفاصيل المنتجات (`Search & Item Cache`).
  - مطابقة وحفظ عروض eBay وتحديثها دون تكرار (`Idempotent Ingestion`).
  - حجب البيانات الحساسة والحمولة الخام افتراضياً لحماية الأمان وسرعة النقل.
- [x] **المرحلة الخامسة (الحالية):** طبقة الذكاء الاصطناعي التجاري (AI Commerce Intelligence Layer):
  - بناء معمارية مزودي الذكاء الاصطناعي التجريدية (`AIProviderInterface`, `OpenAIProvider`, `MockAIProvider`).
  - ضبط مبدأ حقيقة البيانات الصارمة (`Data Truth`: OBSERVED, CALCULATED, ESTIMATED, HEURISTIC, AI_GENERATED).
  - عزل وتفويض الحسابات المالية الحتمية لـ Laravel بواسطة BCMath ومنع AI من اختلاق الأرقام.
  - فصل سمعة البائع (`seller_feedback`) عن جودة المنتج ومبيعاته لمنع التضليل.
  - محرك تشكيل السياق المحمي (`AIContextBuilder`) وجدار حماية ضد Prompt Injection.
  - إدارة وإصدار التعليمات الهندسية للنموذج (`ProductAnalysisPromptV1`).
  - مدقق وصمام أمان الاستجابات الهيكلية الصارمة (`AIResponseValidator`).
  - محرك التحكيم التجاري والتوصيات (`AIRecommendationEngine`: BUY, WATCH, AVOID, INSUFFICIENT_DATA).
  - توثيق وتخزين عمليات التحليل في جدول تدقيق تاريخي (`ai_analyses`).
  - واجهات API متقدمة ومحمية بـ Sanctum و Rate Limiting.
- [ ] **المرحلة السادسة (المستقبلية):** تطبيق الهاتف المحمول (Flutter Mobile App).

---

## 🛠️ متطلبات التشغيل (System Requirements)

- **نظام التشغيل:** Windows 10/11 (أو Linux / macOS)
- **PHP:** `^8.5` (الإصدار الفعلي: `PHP 8.5.12`)
- **Composer:** `^2.11` (الإصدار الفعلي: `Composer 2.11.1`)
- **خادم قواعد البيانات:** MySQL / MariaDB (عبر XAMPP - الإصدار الفعلي: `MariaDB 10.11.16`)
- **إضافات PHP المطلوبة (PHP Extensions):**
  - `pdo_mysql`, `bcmath`, `curl`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip`

---

## 🛒 دليل تكامل eBay Browse API (Phase 4 Integration Guide)

### 1. إعداد حساب المطور وتطبيق eBay (eBay Developer Setup)
1. قم بإنشاء حساب مجاني على بوابة مطوري eBay: [eBay Developer Portal](https://developer.ebay.com/).
2. أنشئ تطبيقاً جديداً (Application Key Sets) للحصول على:
   - **Client ID (App ID)**
   - **Client Secret (Cert ID)**
3. يوفر eBay بيئتين:
   - **Sandbox:** بيئة تجريبية للاختبارات تحاكي واجهات الـ API.
   - **Production:** بيئة الإنتاج الفعلية التي تعيد ملايين المنتجات الحقيقية المعروضة للبيع عالمياً.

### 2. إعداد المتغيرات في ملف `.env`:
أضف المتغيرات التالية في ملف `.env` المحلي (ممنوع رفع المفاتيح لـ Git):
```env
# نمط التشغيل: mock (بدون إنترنت) | sandbox (تجريبي) | production (حي)
EBAY_MODE=mock
EBAY_CLIENT_ID=your_ebay_app_id_here
EBAY_CLIENT_SECRET=your_ebay_cert_id_here
EBAY_MARKETPLACE_ID=EBAY_US
EBAY_TIMEOUT=15
EBAY_RETRY_ATTEMPTS=2
EBAY_RETRY_BACKOFF_MS=500
EBAY_CACHE_TTL_SEARCH=900
EBAY_CACHE_TTL_ITEM=3600
```

### 3. تدفق التوثيق (OAuth 2.0 Application Token Flow):
- يقوم [EbayAuthService.php](file:///d:/2026/Desktop/مقررات%20مستوى%20ثالث/ترم%20ثاني/هندسة%20برمجيات/project/AI_Assistant/ai-commerce-assistant/app/Services/ExternalSources/Ebay/EbayAuthService.php) بإرسال طلب `POST` إلى `/identity/v1/oauth2/token` باستخدام Basic Auth مشفر ومبني من Client ID و Client Secret.
- يتم تخزين التوكن تلقائياً في Cache النظام لمدة صلاحيته (7200 ثانية - 300 ثانية كهامش أمان).
- إذا انتهت صلاحية التوكن أثناء العمل (401)، يقوم النظام آلياً بإلغاء التوكن المخزن وطلب توكن جديد وإعادة المحاولة لمرة واحدة دون تعطيل المستخدم.

### 4. تدفق البحث والاسترجاع المالي (End-to-End Pipeline):
```text
Client Request: GET /api/v1/market-research/ebay/search?q=wireless+printer
       │
       ▼
EbaySearchRequest (Input Validation & Type Sanitization)
       │
       ▼
MarketResearchService (Check Cache -> Resolve Connector)
       │
       ├─► [Cache Hit] ──────────────────────────────────────────► Instant Response
       │
       ▼ [Cache Miss]
EbayConnector (or MockEbayConnector if mode=mock)
       │
       ▼ OAuth Bearer Token + Retry with Exponential Backoff
eBay Browse API (GET /buy/browse/v1/item_summary/search)
       │
       ▼ Raw JSON Response
EbayProductNormalizer (Extract external_id, title, price, currency, rating, reviews)
       │
       ▼ ExternalProductDto
ProductIngestionService (Idempotent updateOrCreate by source_id + external_id)
       │
       ▼
ProductMatchingService (Deterministic heuristic matching against CanonicalProduct)
       │
       ▼
Database Persistence (product_listings linked to canonical_products)
       │
       ▼
API Response (MarketSearchResultResource - raw_payload suppressed by default)
```

---

## 🌐 واجهات برمجة التطبيقات (API Reference - `/api/v1`)

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | فحص جاهزية النظام والخدمة | ❌ لا |
| `POST` | `/api/v1/auth/register` | تسجيل باحث/مستخدم جديد | ❌ لا (محدد: 10/min) |
| `POST` | `/api/v1/auth/login` | تسجيل الدخول وإصدار Bearer Token | ❌ لا (محدد: 10/min) |
| `GET` | `/api/v1/auth/me` | استعراض بيانات الحساب الحالي | ✅ نعم (Bearer Token) |
| `POST` | `/api/v1/auth/logout` | تسجيل الخروج وإلغاء صلاحية Token | ✅ نعم (Bearer Token) |
| `GET` | `/api/v1/categories` | شجرة التصنيفات الهرمية | ❌ لا |
| `GET` | `/api/v1/sources` | قائمة المصادر والأسواق المعتمدة | ❌ لا |
| `GET` | `/api/v1/products` | استعراض المنتجات المرجعية مع ملخص العروض والأسعار | ❌ لا |
| `GET` | `/api/v1/products/{id}` | تفاصيل المنتج المرجعي مع مؤشرات الجدوى | ❌ لا |
| `GET` | `/api/v1/products/{id}/listings` | قائمة عروض الأسواق المرتبطة بالمنتج المرجعي | ❌ لا |
| `GET` | `/api/v1/products/{id}/price-comparison` | مقارنة الأسعار المجمعة وهوامش الربح بين الأسواق | ❌ لا |
| `GET` | `/api/v1/listings` | استعراض عروض الأسواق المباشرة مع الفلترة والترقيم | ❌ لا |
| `GET` | `/api/v1/listings/{id}` | تفاصيل عرض محدد في سوق خارجي | ❌ لا (`?include_raw=true` للحمولة الخام) |
| **`GET`** | **`/api/v1/market-research/ebay/search`** | **البحث المباشر في سوق eBay وجلب وتخزين العروض ومطابقتها** | ❌ لا (محدد: 60/min) |
| **`GET`** | **`/api/v1/market-research/ebay/items/{id}`** | **جلب التفاصيل الحية لعنصر محدد من سوق eBay بواسطة Item ID** | ❌ لا (محدد: 60/min) |
| `POST` | `/api/v1/analysis/calculate` | أداة التحليل المالي السريع المستقل (BCMath) | ❌ لا |
| `POST` | `/api/v1/products/{id}/analyze` | إجراء تحليل مالي وحفظ سجل Snapshot للمنتج | ✅ نعم (Bearer Token) |
| **`POST`** | **`/api/v1/ai/products/{id}/analyze`** | **تحليل ذكي شامل للمنتج المرجعي وتقديم توصية وخطة عمل استراتيجية** | ✅ نعم (محدد: 15/min) |
| **`POST`** | **`/api/v1/ai/listings/{id}/analyze`** | **تحليل ذكي لعرض سوق محدد وتقييم جدوى الشراء أو الدروبشيبنج** | ✅ نعم (محدد: 15/min) |
| **`GET`** | **`/api/v1/ai/analyses/{id}`** | **استرجاع نتائج تحليل ذكي سابقة مع سياق الإدخال والبيانات الحتمية** | ✅ نعم (Bearer Token) |

---

## 🧠 طبقة الذكاء الاصطناعي التجاري (Phase 5 - AI Commerce Intelligence)

### 1. فلسفة المعمارية ومبدأ حقيقة البيانات (Data Truth Hierarchy)
```text
           [User / API Client]
                   │
                   ▼
       Laravel API Controller
                   │
       ┌───────────┴───────────┐
       ▼                       ▼
ProductAnalysisEngine    AIContextBuilder
 (BCMath Deterministic)   (Data Truth Tagging)
       │                       │
       └───────────┬───────────┘
                   ▼
         AIProviderInterface
        (OpenAI / Mock Engine)
                   │
                   ▼
          AIResponseValidator
      (Schema & Arbiter Override)
                   │
                   ▼
        AIRecommendationEngine
     (BUY / WATCH / AVOID / DATA)
                   │
                   ▼
       Database (`ai_analyses`)
```

- **Laravel = مصدر الحقيقة الرياضية والحسابية:** تُحسب جميع الأرقام المالية (التكلفة الإجمالية، الربح الصافي، هامش الربح %، العائد على الاستثمار %) حتمياً بدقة `BCMath` العشرية، ويُمنع الذكاء الاصطناعي منعاً باتاً من إعادة حسابها أو اختلاق أرقام بديلة.
- **AI = التفسير والاستدلال والاستراتيجية:** يتولى الذكاء الاصطناعي تقييم الفرصة، دراسة المنافسة، تحليل المخاطر، ووضع خطة العمل (Action Plan).
- **تصنيف حقيقة البيانات (Data Truth Tags):**
  - `OBSERVED`: حقائق مرصودة من الأسواق (أسعار، أسماء بائعين، روابط).
  - `CALCULATED`: حسابات مالية حتمية محسوبة في Laravel.
  - `ESTIMATED`: تقديرات يدخلها المستخدم أو تقديرات أساسية (الشحن، العمولات).
  - `HEURISTIC`: مؤشرات خوارزمية قطعية (Opportunity Score, Risk Score).
  - `AI_GENERATED`: تحليلات وتفسيرات وتوصيات مولدة بالذكاء الاصطناعي.

### 2. التكوين في `.env`:
```env
# نمط المزود: mock (حتمي وسريع بدون تكاليف) | openai (باستخدام OpenAI API)
AI_PROVIDER=mock
AI_PROMPT_VERSION=v1
AI_API_KEY=sk-your-openai-key-here
AI_MODEL=gpt-4o-mini
AI_TIMEOUT=30
AI_MAX_RETRIES=2
AI_RATE_LIMIT_PER_MINUTE=15
```

### 3. صمام الأمان والتحكيم المالي (Arbiter Override):
إذا أظهرت الحسابات الحتمية في Laravel أن المنتج خاسر أو بهامش ربح سلبي (`profit <= 0`) أو عند غياب عروض السوق تماماً، وقام الذكاء الاصطناعي باختلاق توصية `BUY`، يقوم `AIResponseValidator` فورياً بنقض التوصية وتحويلها إلى `AVOID` أو `INSUFFICIENT_DATA` مع توثيق التحكيم في التحذيرات (`warnings`).

---

## 🧪 الاختبارات الآلية (Automated Testing Suite)

يتضمن المشروع الآن **113 اختباراً مؤتمتاً** يغطي كافة الجوانب المعمارية والتكاملية والأمنية وطبقة الذكاء الاصطناعي:

```bash
php artisan test
```

### نتيجة الاختبارات الإجمالية:
- **إجمالي الاختبارات:** 113 اختباراً مؤتمتاً (`113 tests`)
- **حالة النجاح:** 113 ناجحاً بنسبة **100%** (`passed: 113`)
- **عدد الفحوصات والتأكيدات:** 723 تأكيداً (`723 assertions`)
- **زمن التنفيذ:** ~10 ثوانٍ
