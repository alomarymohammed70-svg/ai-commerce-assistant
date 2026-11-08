import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_strings.dart';

class DecisionUiHelper {
  static Color getDecisionColor(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return AppColors.buy;
      case 'WATCH':
        return AppColors.watch;
      case 'AVOID':
        return AppColors.avoid;
      case 'INSUFFICIENT_DATA':
      default:
        return AppColors.decisionInsufficient;
    }
  }

  static Color getDecisionBgColor(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return AppColors.buyLight;
      case 'WATCH':
        return AppColors.watchLight;
      case 'AVOID':
        return AppColors.avoidLight;
      case 'INSUFFICIENT_DATA':
      default:
        return AppColors.decisionInsufficientBg;
    }
  }

  static Color getDecisionBorderColor(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return AppColors.buyBorder;
      case 'WATCH':
        return AppColors.watchBorder;
      case 'AVOID':
        return AppColors.avoidBorder;
      case 'INSUFFICIENT_DATA':
      default:
        return AppColors.decisionInsufficientBorder;
    }
  }

  static IconData getDecisionIcon(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return Icons.check_circle_rounded;
      case 'WATCH':
        return Icons.visibility_rounded;
      case 'AVOID':
        return Icons.cancel_rounded;
      case 'INSUFFICIENT_DATA':
      default:
        return Icons.help_outline_rounded;
    }
  }

  static String getDecisionLabel(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return 'شراء (BUY)';
      case 'WATCH':
        return 'مراقبة (WATCH)';
      case 'AVOID':
        return 'تجنب (AVOID)';
      case 'INSUFFICIENT_DATA':
      default:
        return 'بيانات غير كافية';
    }
  }

  static String getDecisionTitleArabic(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return 'فرصة شراء واعدة (BUY)';
      case 'WATCH':
        return 'مراقبة ومتابعة (WATCH)';
      case 'AVOID':
        return 'تجنب الشراء (AVOID)';
      case 'INSUFFICIENT_DATA':
      default:
        return 'بيانات غير كافية (INSUFFICIENT DATA)';
    }
  }

  static String getDecisionBadgeText(String? decision) {
    switch (decision?.toUpperCase()) {
      case 'BUY':
        return 'شراء مُرجّح';
      case 'WATCH':
        return 'قيد المراقبة';
      case 'AVOID':
        return 'غير مجدٍ';
      case 'INSUFFICIENT_DATA':
      default:
        return 'بيانات ناقصة';
    }
  }

  static String getTruthBadgeArabic(String? truthTag) {
    switch (truthTag?.toUpperCase()) {
      case 'OBSERVED':
        return AppStrings.truthObserved;
      case 'CALCULATED':
        return AppStrings.truthCalculated;
      case 'ESTIMATED':
        return AppStrings.truthEstimated;
      case 'HEURISTIC':
        return AppStrings.truthHeuristic;
      case 'AI_GENERATED':
        return AppStrings.truthAiGenerated;
      default:
        return truthTag ?? 'غير محدد';
    }
  }

  static Widget buildDecisionBadge(String? decision) {
    final color = getDecisionColor(decision);
    final bgColor = getDecisionBgColor(decision);
    final borderColor = getDecisionBorderColor(decision);
    final icon = getDecisionIcon(decision);
    final label = getDecisionLabel(decision);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor, width: 1.2),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }

  static Widget buildDataTruthBadge(String? status, {bool isShippingVerified = true}) {
    final isUnverified = status == 'ESTIMATED_PENDING_SHIPPING' || !isShippingVerified;

    if (isUnverified) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: AppColors.watchLight,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: AppColors.watchBorder),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.warning_amber_rounded, size: 16, color: AppColors.watch),
            SizedBox(width: 6),
            Text(
              '⚠️ أرباح تقديرية — الشحن غير مؤكد',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: AppColors.watch,
              ),
            ),
          ],
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: AppColors.buyLight,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: AppColors.buyBorder),
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.check_circle_outline, size: 16, color: AppColors.buy),
          SizedBox(width: 6),
          Text(
            '✓ أرباح مؤكدة — الشحن موثق',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: AppColors.buy,
            ),
          ),
        ],
      ),
    );
  }
}
