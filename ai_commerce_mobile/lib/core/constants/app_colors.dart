import 'package:flutter/material.dart';

class AppColors {
  // Brand Colors
  static const Color primary = Color(0xFF1E3A8A); // Deep Navy / Indigo
  static const Color primaryLight = Color(0xFF3B82F6);
  static const Color primaryDark = Color(0xFF0F172A);
  static const Color accent = Color(0xFF0D9488); // Teal

  // Backgrounds & Surfaces (Light)
  static const Color backgroundLight = Color(0xFFF8FAFC);
  static const Color surfaceLight = Colors.white;
  static const Color cardLight = Colors.white;
  static const Color borderLight = Color(0xFFE2E8F0);

  // Backgrounds & Surfaces (Dark)
  static const Color backgroundDark = Color(0xFF0B1120);
  static const Color surfaceDark = Color(0xFF151F32);
  static const Color cardDark = Color(0xFF1E293B);
  static const Color borderDark = Color(0xFF334155);

  // Text Colors
  static const Color textPrimaryLight = Color(0xFF0F172A);
  static const Color textSecondaryLight = Color(0xFF64748B);
  static const Color textPrimaryDark = Color(0xFFF8FAFC);
  static const Color textSecondaryDark = Color(0xFF94A3B8);

  // AI Commercial Decision Semantic Colors
  static const Color decisionBuy = Color(0xFF059669); // Emerald 600
  static const Color decisionBuyBg = Color(0xFFECFDF5);
  static const Color decisionWatch = Color(0xFFD97706); // Amber 600
  static const Color decisionWatchBg = Color(0xFFFFFBEB);
  static const Color decisionAvoid = Color(0xFFDC2626); // Red 600
  static const Color decisionAvoidBg = Color(0xFFFEF2F2);
  static const Color decisionInsufficientData = Color(0xFF475569); // Slate 600
  static const Color decisionInsufficientDataBg = Color(0xFFF1F5F9);

  // Quick Semantic Aliases
  static const Color buy = decisionBuy;
  static const Color buyLight = decisionBuyBg;
  static const Color buyBorder = Color(0xFFA7F3D0);

  static const Color watch = decisionWatch;
  static const Color watchLight = decisionWatchBg;
  static const Color watchBorder = Color(0xFFFDE68A);

  static const Color avoid = decisionAvoid;
  static const Color avoidLight = decisionAvoidBg;
  static const Color avoidBorder = Color(0xFFFECACA);

  static const Color decisionInsufficient = decisionInsufficientData;
  static const Color decisionInsufficientBg = decisionInsufficientDataBg;
  static const Color decisionInsufficientBorder = Color(0xFFCBD5E1);

  // Status Colors
  static const Color success = Color(0xFF10B981);
  static const Color warning = Color(0xFFF59E0B);
  static const Color error = Color(0xFFEF4444);
  static const Color info = Color(0xFF3B82F6);
}
