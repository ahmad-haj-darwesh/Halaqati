import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// لوحة ألوان التطبيق (أخضر/ذهبي) للاستخدام في الثيم والويدجتات.
///
/// Arabic: ألوان ثابتة تُستدعى من `buildAppTheme` وبعض الشاشات.
/// EN: Brand color tokens shared across the app theme.
abstract final class AppColors {
  static const Color deepGreen = Color(0xFF0D3D2E);
  static const Color forest = Color(0xFF1B5E4A);
  static const Color mint = Color(0xFF2A9D7A);
  static const Color gold = Color(0xFFC9A227);
  static const Color goldLight = Color(0xFFE8D48A);
  static const Color surface = Color(0xFFF4F7F5);
  static const Color surfaceCard = Color(0xFFFFFFFF);
}

/// بناء `ThemeData` الموحّد (Material 3 + خط Tajawal + RTL-friendly styles).
///
/// Arabic: يضبط الأزرار، الحقول، البطاقات، وشريط التنقّل السفلي.
/// EN: Central Material 3 theme using Tajawal and brand colors.
ThemeData buildAppTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.mint,
      primary: AppColors.forest,
      secondary: AppColors.gold,
      surface: AppColors.surface,
      brightness: Brightness.light,
    ),
  );

  final textTheme = GoogleFonts.tajawalTextTheme(base.textTheme).apply(
    bodyColor: const Color(0xFF1A1F1C),
    displayColor: const Color(0xFF1A1F1C),
  );

  return base.copyWith(
    textTheme: textTheme,
    scaffoldBackgroundColor: AppColors.surface,
    appBarTheme: AppBarTheme(
      centerTitle: true,
      elevation: 0,
      scrolledUnderElevation: 0.5,
      backgroundColor: AppColors.deepGreen,
      foregroundColor: Colors.white,
      titleTextStyle: GoogleFonts.tajawal(
        color: Colors.white,
        fontSize: 18,
        fontWeight: FontWeight.w600,
      ),
    ),
    cardTheme: CardThemeData(
      color: AppColors.surfaceCard,
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      margin: EdgeInsets.zero,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        backgroundColor: AppColors.forest,
        foregroundColor: Colors.white,
        textStyle: GoogleFonts.tajawal(fontWeight: FontWeight.w600, fontSize: 15),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        side: const BorderSide(color: AppColors.forest, width: 1.2),
        foregroundColor: AppColors.forest,
        textStyle: GoogleFonts.tajawal(fontWeight: FontWeight.w600, fontSize: 15),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.mint, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    ),
    chipTheme: ChipThemeData(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      side: BorderSide.none,
      labelStyle: GoogleFonts.tajawal(fontSize: 13),
    ),
    navigationBarTheme: NavigationBarThemeData(
      indicatorColor: AppColors.mint.withValues(alpha: 0.2),
      labelTextStyle: WidgetStatePropertyAll(
        GoogleFonts.tajawal(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    ),
  );
}
