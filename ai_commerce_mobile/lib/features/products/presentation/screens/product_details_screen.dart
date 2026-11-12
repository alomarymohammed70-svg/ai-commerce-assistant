import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:ai_commerce_mobile/core/constants/app_colors.dart';
import 'package:ai_commerce_mobile/core/constants/app_strings.dart';
import 'package:ai_commerce_mobile/core/utils/currency_formatter.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/presentation/screens/ai_analysis_screen.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/screens/price_comparison_screen.dart';
import 'package:ai_commerce_mobile/features/products/data/models/canonical_product_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/product_listing_model.dart';
import 'package:ai_commerce_mobile/features/products/presentation/providers/product_notifier.dart';

class ProductDetailsScreen extends ConsumerWidget {
  final int productId;

  const ProductDetailsScreen({super.key, required this.productId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final productAsync = ref.watch(productDetailsProvider(productId));

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.productDetails),
      ),
      body: productAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 48, color: AppColors.error),
                const SizedBox(height: 12),
                Text(
                  err.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.error),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => ref.invalidate(productDetailsProvider(productId)),
                  child: const Text(AppStrings.retry),
                ),
              ],
            ),
          ),
        ),
        data: (product) => _buildProductContent(context, product),
      ),
    );
  }

  Widget _buildProductContent(BuildContext context, CanonicalProductModel product) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Product Header Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          product.categoryName ?? 'عام',
                          style: const TextStyle(
                            color: AppColors.primary,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const Spacer(),
                      if (product.brand != null)
                        Text(
                          'العلامة: ${product.brand}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondaryLight,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    product.name,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  if (product.description != null && product.description!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      product.description!,
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight),
                    ),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Price Summary & Opportunity Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            AppStrings.lowestPrice,
                            style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                          ),
                          Text(
                            product.lowestPrice != null
                                ? CurrencyFormatter.format(product.lowestPrice, currency: product.priceSummary.currency)
                                : AppStrings.dataUnavailable,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: product.lowestPrice != null
                                  ? AppColors.decisionBuy
                                  : AppColors.textSecondaryLight,
                            ),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text(
                            AppStrings.highestPrice,
                            style: TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                          ),
                          Text(
                            product.highestPrice != null
                                ? CurrencyFormatter.format(product.highestPrice, currency: product.priceSummary.currency)
                                : AppStrings.dataUnavailable,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: product.highestPrice != null
                                  ? null
                                  : AppColors.textSecondaryLight,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  if (product.averagePrice != null) ...[
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerRight,
                      child: Text(
                        'متوسط السعر: ${CurrencyFormatter.format(product.averagePrice, currency: product.priceSummary.currency)}',
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
                      ),
                    ),
                  ],
                  if (product.latestOpportunityScore != null) ...[
                    const Divider(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          AppStrings.opportunityScore,
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${product.latestOpportunityScore!.toStringAsFixed(1)} / 100',
                            style: const TextStyle(
                              color: AppColors.primary,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Primary Action Buttons: Price Comparison & AI Analysis
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => PriceComparisonScreen(productId: product.id),
                      ),
                    );
                  },
                  icon: const Icon(Icons.compare_arrows_rounded),
                  label: const Text(AppStrings.priceComparison),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => AiAnalysisScreen(
                          canonicalProductId: product.id,
                          productTitle: product.name,
                          defaultPurchasePrice: product.lowestPrice,
                          defaultSellingPrice: product.highestPrice,
                        ),
                      ),
                    );
                  },
                  icon: const Icon(Icons.psychology_rounded),
                  label: const Text(AppStrings.aiAnalysis),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Market Listings Section Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                AppStrings.marketOffers,
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              Text(
                '${product.activeListingsCount} نشط / ${product.totalListingsCount} إجمالي',
                style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryLight),
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Market Offers List
          if (product.listings.isEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Center(
                  child: Text(
                    AppStrings.noResults,
                    style: const TextStyle(color: AppColors.textSecondaryLight),
                  ),
                ),
              ),
            )
          else
            ...product.listings.map((offer) => _buildOfferCard(offer)),
        ],
      ),
    );
  }

  Widget _buildOfferCard(ProductListingModel offer) {
    final sourceLabel = offer.sourceName.isNotEmpty
        ? offer.sourceName
        : (offer.sourceCode.isNotEmpty ? offer.sourceCode.toUpperCase() : AppStrings.dataUnavailable);
    final sellerLabel = offer.seller?.displayName ?? AppStrings.dataUnavailable;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
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
                    sourceLabel,
                    style: const TextStyle(
                      color: Colors.blue,
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const Spacer(),
                Text(
                  offer.price != null
                      ? CurrencyFormatter.format(offer.price, currency: offer.currency)
                      : 'غير متاح',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: offer.price != null
                        ? AppColors.primary
                        : AppColors.textSecondaryLight,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              offer.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 10),

            // Safe Seller Signals (no raw_payload, no fake placeholder)
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppColors.backgroundLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.storefront_outlined, size: 16, color: AppColors.textSecondaryLight),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      sellerLabel,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
                    ),
                  ),
                  const SizedBox(width: 8),
                  if (offer.seller?.feedbackPercentage != null) ...[
                    const Icon(Icons.star_rounded, size: 14, color: Colors.amber),
                    const SizedBox(width: 2),
                    Text(
                      '${offer.seller!.feedbackPercentage}% تقييم البائع',
                      style: const TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                    ),
                  ] else
                    const Text(
                      AppStrings.dataUnavailable,
                      style: TextStyle(fontSize: 11, color: AppColors.textSecondaryLight),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
