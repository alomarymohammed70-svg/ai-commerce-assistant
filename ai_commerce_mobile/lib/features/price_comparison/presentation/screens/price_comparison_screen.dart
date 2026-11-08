import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:ai_commerce_mobile/core/constants/app_colors.dart';
import 'package:ai_commerce_mobile/core/constants/app_strings.dart';
import 'package:ai_commerce_mobile/core/utils/currency_formatter.dart';
import 'package:ai_commerce_mobile/features/price_comparison/data/models/price_comparison_model.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/providers/price_comparison_provider.dart';

class PriceComparisonScreen extends ConsumerWidget {
  final int productId;

  const PriceComparisonScreen({super.key, required this.productId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final comparisonAsync = ref.watch(priceComparisonProvider(productId));

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.priceComparison),
      ),
      body: comparisonAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 48, color: AppColors.error),
                const SizedBox(height: 12),
                Text(err.toString(), textAlign: TextAlign.center, style: const TextStyle(color: AppColors.error)),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => ref.refresh(priceComparisonProvider(productId)),
                  child: const Text(AppStrings.retry),
                ),
              ],
            ),
          ),
        ),
        data: (comparison) => _buildComparisonContent(context, comparison),
      ),
    );
  }

  Widget _buildComparisonContent(BuildContext context, PriceComparisonModel data) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    data.productName,
                    style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'تم توحيد وحساب الأسعار بعملة: ${data.targetCurrency} بواسطة خادم Laravel',
                    style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Price Spread Arbitrage Metric Card
          Card(
            color: AppColors.primary.withValues(alpha: 0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.trending_up_rounded, color: AppColors.primary),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          AppStrings.priceSpread,
                          style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                        ),
                        Text(
                          CurrencyFormatter.format(data.maxPriceSpread, currency: data.targetCurrency),
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.primary),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Offers List
          Text(
            '${AppStrings.marketOffers} (${data.offersCount})',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),

          if (data.offers.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24.0),
                child: Center(
                  child: Text(AppStrings.noResults, style: TextStyle(color: AppColors.textSecondaryLight)),
                ),
              ),
            )
          else
            ...data.offers.map((offer) => _buildOfferComparisonCard(offer, data.targetCurrency)),
        ],
      ),
    );
  }

  Widget _buildOfferComparisonCard(ComparisonOfferModel offer, String targetCurrency) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.blue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    offer.source.toUpperCase(),
                    style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 11),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  offer.sellerUsername ?? 'بائع معتمد',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
                ),
                const Spacer(),
                Text(
                  CurrencyFormatter.format(offer.convertedPrice, currency: targetCurrency),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primary),
                ),
              ],
            ),
            if (offer.originalCurrency != targetCurrency) ...[
              const SizedBox(height: 6),
              Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'السعر الأصلي: ${CurrencyFormatter.format(offer.originalPrice, currency: offer.originalCurrency)}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                ),
              ),
            ],
            if (offer.sellerFeedbackPercentage != null) ...[
              const Divider(height: 16),
              Row(
                children: [
                  const Icon(Icons.star_rounded, size: 14, color: Colors.amber),
                  const SizedBox(width: 4),
                  Text(
                    'تقييم البائع بالمتجر: ${offer.sellerFeedbackPercentage}%',
                    style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
