import 'package:flutter/material.dart';

import '../core/auth/app_role.dart';
import '../features/auth/presentation/login_page.dart';
import '../features/examiner/presentation/examiner_shell.dart';
import '../features/home/presentation/home_page.dart';
import '../features/supervisor/presentation/supervisor_home_page.dart';

/// الشاشة الرئيسية حسب دور المستخدم بعد تسجيل الدخول.
///
/// Arabic: نقطة توجيه مركزية (Router) تربط `role` القادم من الباكند 
/// أو التخزين المحلي بالواجهة الرئيسية المناسبة.
/// EN: Central role-to-home router for the mobile app.
class RoleHome extends StatelessWidget {
  final String role;

  const RoleHome({super.key, required this.role});

  @override
  Widget build(BuildContext context) {
    // توجيه المستخدم بناءً على دوره بصيغة نظيفة ومباشرة
    switch (role) {
      case AppRole.teacher:
        return const HomePage();
      
      case AppRole.examiner:
        return const ExaminerShellPage();
      
      case AppRole.centerSupervisor:
        return const SupervisorHomePage();
      
      default:
        // في حال كان الدور غير معروف (أو حدث خطأ)، نرجعه لشاشة الدخول 
        // كإجراء أمني (Fallback) لضمان عدم البقاء في حالة غير محددة.
        return const LoginPage();
    }
  }
}