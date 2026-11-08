import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/utils/currency_formatter.dart';
import '../../../../core/utils/decision_ui_helper.dart';
import '../../data/models/ai_analysis_model.dart';
import '../providers/ai_analysis_notifier.dart';

class AiAnalysisScreen extends ConsumerStatefulWidget {
  final int canonicalProductId;
  final String productTitle;
  final double? defaultPurchasePrice;
  final double? defaultSellingPrice;
  final double? defaultShippingCost;

  const AiAnalysisScreen({
    super.key,
    required this.canonicalProductId,
    required this.productTitle,
    this.defaultPurchasePrice,
    this.defaultSellingPrice,
    this.defaultShippingCost,
  });

  @override
  ConsumerState<AiAnalysisScreen> createState() => _AiAnalysisScreenState();
}

class _AiAnalysisScreenState extends ConsumerState<AiAnalysisScreen> {
  late final TextEditingController _purchasePriceController;
  late final TextEditingController _sellingPriceController;
  late final TextEditingController _shippingCostController;
  late final TextEditingController _feesController;

  String _selectedDriver = 'mock'; // default to mock or openai
  bool _showCustomInputs = false;

  @override
  void initState() {
    super.initState();
    _purchasePriceController = TextEditingController(
      text: widget.defaultPurchasePrice != null && widget.defaultPurchasePrice! > 0
          ? widget.defaultPurchasePrice!.toStringAsFixed(2)
          : '',
    );
    _sellingPriceController = TextEditingController(
      text: widget.defaultSellingPrice != null && widget.defaultSellingPrice! > 0
          ? widget.defaultSellingPrice!.toStringAsFixed(2)
          : '',
    );
    _shippingCostController = TextEditingController(
      text: widget.defaultShippingCost != null
          ? widget.defaultShippingCost!.toStringAsFixed(2)
          : '15.00',
    );
    _feesController = TextEditingController(text: '10.00');

    // Automatically trigger initial analysis on screen open
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _runAnalysis();
    });
  }

  @override
  void dispose() {
    _purchasePriceController.dispose();
    _sellingPriceController.dispose();
    _shippingCostController.dispose();
    _feesController.dispose();
    super.dispose();
  }

  void _runAnalysis() {
    final purchasePrice = double.tryParse(_purchasePriceController.text.trim());
    final sellingPrice = double.tryParse(_sellingPriceController.text.trim());
    final shippingCost = double.tryParse(_shippingCostController.text.trim());
    final fees = double.tryParse(_feesController.text.trim());

    ref.read(aiAnalysisNotifierProvider.notifier).analyzeProduct(
          canonicalProductId: widget.canonicalProductId,
          purchasePrice: purchasePrice,
          sellingPrice: sellingPrice,
          shippingCost: shippingCost,
          estimatedFees: fees,
          driver: _selectedDriver,
        );
  }

  @override
  Widget build(BuildContext context) {
    final analysisState = ref.watch(aiAnalysisNotifierProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.aiAnalysisTitle),
        actions: [
          IconButton(
            icon: Icon(_showCustomInputs ? Icons.tune : Icons.tune_outlined),
            tooltip: 'تعديل المعطيات المالية',
            onPressed: () {
              setState(() {
                _showCustomInputs = !_showCustomInputs;
              });
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: AppStrings.retry,
            onPressed: _runAnalysis,
          ),
        ],
      ),
      body: Directionality(
        textDirection: TextDirection.rtl,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Product Header
              _buildProductHeader(),
              const SizedBox(height: 16),

              // Optional Custom Inputs Panel
              if (_showCustomInputs) ...[
                _buildInputsCard(),
                const SizedBox(height: 16),
              ],

              // Analysis Body
              analysisState.when(
                data: (analysis) {
                  if (analysis == null) {
                    return _buildInitialPrompt();
                  }
                  return _buildAnalysisContent(analysis);
                },
                loading: () => _buildLoadingState(),
                error: (error, _) => _buildErrorState(error.toString()),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProductHeader() {
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.primaryContainer.withAlpha(50),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).colorScheme.primary.withAlpha(50)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withAlpha(30),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.auto_awesome,
                color: Theme.of(context).colorScheme.primary,
                size: 26,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.productTitle,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'معرف المنتج: #${widget.canonicalProductId}',
                    style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputsCard() {
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.tune, size: 20, color: AppColors.primary),
                const SizedBox(width: 8),
                const Text(
                  'المعطيات المالية المخصصة للتحليل',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _purchasePriceController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'سعر الشراء (\$)',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _sellingPriceController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'سعر البيع (\$)',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _shippingCostController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'الشحن التقديري (\$)',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _feesController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'العمولات التقديرية (\$)',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Text('مزود الذكاء: ', style: TextStyle(fontSize: 13)),
                const SizedBox(width: 8),
                ChoiceChip(
                  label: const Text('Mock Engine'),
                  selected: _selectedDriver == 'mock',
                  onSelected: (val) {
                    if (val) setState(() => _selectedDriver = 'mock');
                  },
                ),
                const SizedBox(width: 8),
                ChoiceChip(
                  label: const Text('OpenAI'),
                  selected: _selectedDriver == 'openai',
                  onSelected: (val) {
                    if (val) setState(() => _selectedDriver = 'openai');
                  },
                ),
              ],
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _runAnalysis,
                icon: const Icon(Icons.bolt),
                label: const Text('إعادة التحليل بالمعطيات الجديدة'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLoadingState() {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
        child: Column(
          children: [
            const CircularProgressIndicator(),
            const SizedBox(height: 20),
            const Text(
              'جاري تحليل معطيات السوق والجدوى التجارية...',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'يتم التحقق من الأسعار، حساب الأرباح الحتمية، وتقييم المخاطر والمنافسة.',
              style: TextStyle(fontSize: 13, color: Theme.of(context).colorScheme.onSurfaceVariant),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorState(String message) {
    return Card(
      color: AppColors.avoidLight,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppColors.avoidBorder),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const Icon(Icons.error_outline, color: AppColors.avoid, size: 40),
            const SizedBox(height: 12),
            const Text(
              'تعذر إتمام التحليل الذكي',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.avoid),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              style: const TextStyle(fontSize: 13),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _runAnalysis,
              icon: const Icon(Icons.refresh),
              label: const Text(AppStrings.retry),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInitialPrompt() {
    return Center(
      child: ElevatedButton.icon(
        onPressed: _runAnalysis,
        icon: const Icon(Icons.auto_awesome),
        label: const Text('تشغيل التحليل التجاري الذكي'),
      ),
    );
  }

  Widget _buildAnalysisContent(AIAnalysisModel analysis) {
    final decision = analysis.decision;
    final detail = analysis.analysis;
    final metrics = analysis.deterministicMetrics;

    final decisionColor = DecisionUiHelper.getDecisionColor(decision);
    final decisionBgColor = DecisionUiHelper.getDecisionBgColor(decision);
    final decisionBorderColor = DecisionUiHelper.getDecisionBorderColor(decision);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // 1. Recommendation Banner
        Card(
          elevation: 0,
          color: decisionBgColor,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
            side: BorderSide(color: decisionBorderColor, width: 1.5),
          ),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    DecisionUiHelper.buildDecisionBadge(decision),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: decisionColor.withAlpha(80)),
                      ),
                      child: Text(
                        'مستوى الثقة: ${(analysis.confidence * 100).toStringAsFixed(0)}%',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: decisionColor,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Text(
                  detail.recommendation.summary.isNotEmpty
                      ? detail.recommendation.summary
                      : 'تم إعداد هذا التقييم بناءً على معطيات السوق الحالية والتحليل المالي الحتمي.',
                  style: const TextStyle(fontSize: 14, height: 1.5, fontWeight: FontWeight.w500),
                ),
                const SizedBox(height: 12),
                // Shipping Data Truth Alert
                DecisionUiHelper.buildDataTruthBadge(
                  metrics.profitStatus,
                  isShippingVerified: metrics.isShippingVerified,
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // 2. Score Metrics Grid (Opportunity & Risk)
        Row(
          children: [
            Expanded(
              child: _buildScoreCard(
                title: 'فرصة الاستثمار',
                score: analysis.opportunityScore,
                level: detail.opportunity.level,
                icon: Icons.trending_up,
                color: AppColors.buy,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildScoreCard(
                title: 'مستوى المخاطر',
                score: analysis.riskScore,
                level: detail.risk.level,
                icon: Icons.security,
                color: analysis.riskScore > 50 ? AppColors.avoid : AppColors.watch,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),

        // 3. Deterministic Financial Authority Breakdown
        _buildFinancialSection(metrics),
        const SizedBox(height: 16),

        // 4. Key Reasons
        if (detail.reasons.isNotEmpty) ...[
          _buildListCard(
            title: 'أسباب التوصية الرئيسية',
            icon: Icons.check_circle_outline,
            iconColor: AppColors.buy,
            items: detail.reasons,
          ),
          const SizedBox(height: 16),
        ],

        // 5. Warnings & Risks
        if (detail.warnings.isNotEmpty || detail.risk.factors.isNotEmpty) ...[
          _buildListCard(
            title: 'التحذيرات والمخاطر المحتملة',
            icon: Icons.warning_amber_rounded,
            iconColor: AppColors.avoid,
            items: {
              ...detail.warnings,
              ...detail.risk.factors,
            }.toList(),
          ),
          const SizedBox(height: 16),
        ],

        // 6. Action Plan
        if (detail.actionPlan.isNotEmpty) ...[
          _buildActionPlanCard(detail.actionPlan),
          const SizedBox(height: 16),
        ],

        // 7. Audit & Provenance Footer
        _buildAuditFooter(analysis),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _buildScoreCard({
    required String title,
    required double score,
    required String level,
    required IconData icon,
    required Color color,
  }) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withAlpha(50)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 18, color: color),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              crossAxisAlignment: CrossAxisAlignment.baseline,
              textBaseline: TextBaseline.alphabetic,
              children: [
                Text(
                  score.toStringAsFixed(0),
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
                const Text('/100', style: TextStyle(fontSize: 12, color: Colors.grey)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: color.withAlpha(30),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    level,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: color,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(
              value: (score / 100).clamp(0.0, 1.0),
              backgroundColor: color.withAlpha(30),
              valueColor: AlwaysStoppedAnimation<Color>(color),
              borderRadius: BorderRadius.circular(4),
              minHeight: 6,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFinancialSection(dynamic metrics) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withAlpha(60)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.calculate_outlined, color: AppColors.primary, size: 20),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'التحليل المالي الحتمي (Laravel Authority)',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(20),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    'BCMath',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.primary),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              'الأرقام التالية محسوبة بدقة مركزية بواسطة Backend وتُعرض كحقائق حتمية.',
              style: TextStyle(fontSize: 11, color: Theme.of(context).colorScheme.onSurfaceVariant),
            ),
            const Divider(height: 24),
            _buildFinancialRow('سعر الشراء المرجعي:', CurrencyFormatter.formatUsd(metrics.purchasePrice), 'OBSERVED'),
            _buildFinancialRow('سعر البيع المقترح:', CurrencyFormatter.formatUsd(metrics.sellingPrice), 'OBSERVED'),
            _buildFinancialRow('تكلفة الشحن:', CurrencyFormatter.formatUsd(metrics.shippingCost), metrics.isShippingVerified ? 'OBSERVED' : 'ESTIMATED'),
            _buildFinancialRow('العمولات المتوقعة:', CurrencyFormatter.formatUsd(metrics.estimatedFees), 'ESTIMATED'),
            const Divider(height: 16),
            _buildFinancialRow('إجمالي التكاليف:', CurrencyFormatter.formatUsd(metrics.totalCost), 'CALCULATED', isBold: true),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: metrics.isProfitable ? AppColors.buyLight : AppColors.avoidLight,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: metrics.isProfitable ? AppColors.buyBorder : AppColors.avoidBorder,
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'صافي الربح المتوقع',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: metrics.isProfitable ? AppColors.buy : AppColors.avoid,
                        ),
                      ),
                      Text(
                        CurrencyFormatter.formatUsd(metrics.estimatedProfit),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: metrics.isProfitable ? AppColors.buy : AppColors.avoid,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        'هامش الربح (Margin)',
                        style: TextStyle(
                          fontSize: 11,
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      Text(
                        '${metrics.profitMarginPercent}%',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: metrics.isProfitable ? AppColors.buy : AppColors.avoid,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFinancialRow(String title, String value, String tag, {bool isBold = false}) {
    Color tagColor = Colors.grey;
    if (tag == 'CALCULATED') tagColor = AppColors.buy;
    if (tag == 'ESTIMATED') tagColor = AppColors.watch;
    if (tag == 'OBSERVED') tagColor = AppColors.primary;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 13,
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
            ),
          ),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
            decoration: BoxDecoration(
              color: tagColor.withAlpha(20),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              tag,
              style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: tagColor),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildListCard({
    required String title,
    required IconData icon,
    required Color iconColor,
    required List<String> items,
  }) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withAlpha(50)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: iconColor),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...items.map((item) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        margin: const EdgeInsets.only(top: 6),
                        width: 6,
                        height: 6,
                        decoration: BoxDecoration(
                          color: iconColor,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          item,
                          style: const TextStyle(fontSize: 13, height: 1.4),
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildActionPlanCard(List<String> actions) {
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.surfaceContainerHighest.withAlpha(60),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.withAlpha(50)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.checklist_rounded, size: 20, color: Theme.of(context).colorScheme.primary),
                const SizedBox(width: 8),
                const Text(
                  'خطة العمل المقترحة (Action Plan)',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...actions.asMap().entries.map((entry) {
              final index = entry.key + 1;
              final action = entry.value;
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      radius: 10,
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: Text(
                        '$index',
                        style: const TextStyle(fontSize: 11, color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        action,
                        style: const TextStyle(fontSize: 13, height: 1.4, fontWeight: FontWeight.w500),
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildAuditFooter(AIAnalysisModel analysis) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.withAlpha(20),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          Text(
            'المزود: ${analysis.provider}',
            style: const TextStyle(fontSize: 11, color: Colors.grey),
          ),
          Text(
            'النموذج: ${analysis.model}',
            style: const TextStyle(fontSize: 11, color: Colors.grey),
          ),
          Text(
            'الوقت: ${analysis.durationMs}ms',
            style: const TextStyle(fontSize: 11, color: Colors.grey),
          ),
          Text(
            'الرموز: ${analysis.tokensUsed}',
            style: const TextStyle(fontSize: 11, color: Colors.grey),
          ),
        ],
      ),
    );
  }
}
