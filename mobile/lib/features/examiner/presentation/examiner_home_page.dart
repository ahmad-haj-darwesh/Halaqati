import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/presentation/login_page.dart';
import '../data/examiner_models.dart';
import '../data/examiner_repository.dart';
import '../../../widgets/notification_bell_button.dart';
import '../pages/exam_daily_summary_page.dart';
import '../pages/exam_test_stepper_page.dart';
import 'bloc/examiner_home_cubit.dart'; // مسار الـ Cubit

const _gradientStart = Color(0xFF2563EB);
const _gradientEnd = Color(0xFF1D4ED8);

/// الصفحة الرئيسية للمختبر (Examiner Dashboard) بنظام Cubit.
class ExaminerHomePage extends StatelessWidget {
  const ExaminerHomePage({super.key});

  @override
  Widget build(BuildContext context) {
    // 1. توفير الـ Cubit وبدء تحميل البيانات
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = ExaminerRepositoryImpl(apiClient: apiClient);
        return ExaminerHomeCubit(repo)..loadDashboard();
      },
      child: const _ExaminerHomeView(),
    );
  }
}

class _ExaminerHomeView extends StatelessWidget {
  const _ExaminerHomeView();

  /// تأكيد تسجيل الخروج ثم إعادة المستخدم لشاشة الدخول.
  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تسجيل الخروج'),
        content: const Text('هل تريد تسجيل الخروج من التطبيق؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFDC2626),
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('تسجيل الخروج'),
          ),
        ],
      ),
    );
    
    if (confirmed != true || !context.mounted) return;
    
    final storage = SecureTokenStorage();
    final auth = AuthRepository(apiClient: ApiClient(tokenStorage: storage), tokenStorage: storage);
    await auth.logout();
    
    if (!context.mounted) return;
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F4FA),
      appBar: AppBar(
        title: const Text('لوحة المختبر'),
        actions: [
          const NotificationBellButton(iconColor: Color(0xFF1E293B)),
          IconButton(
            tooltip: 'تسجيل الخروج',
            icon: const Icon(Icons.power_settings_new_rounded),
            color: const Color(0xFFDC2626),
            onPressed: () => _confirmLogout(context),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: _gradientStart,
        onRefresh: () => context.read<ExaminerHomeCubit>().loadDashboard(),
        child: BlocBuilder<ExaminerHomeCubit, ExaminerHomeState>(
          builder: (context, state) {
            if (state is ExaminerHomeLoading) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator(color: _gradientStart)),
                ],
              );
            }
            
            if (state is ExaminerHomeError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  const SizedBox(height: 80),
                  Icon(Icons.error_outline, size: 56, color: Colors.grey.shade500),
                  const SizedBox(height: 16),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Text(state.message, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade800)),
                  ),
                  const SizedBox(height: 24),
                  Center(
                    child: FilledButton(
                      onPressed: () => context.read<ExaminerHomeCubit>().loadDashboard(),
                      child: const Text('إعادة المحاولة'),
                    ),
                  ),
                ],
              );
            }

            if (state is ExaminerHomeLoaded) {
              return _buildDashboardContent(context, state.stats, state.examinerName);
            }

            return const SizedBox();
          },
        ),
      ),
    );
  }

  Widget _buildDashboardContent(BuildContext context, ExaminerStats s, String name) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: const LinearGradient(
              colors: [_gradientStart, _gradientEnd],
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('مرحباً $name', style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text(
                s.pending == 0
                    ? 'لا توجد تعيينات معلّقة حالياً.'
                    : 'لديك ${s.pending} تعييناً بانتظار إتمام الاختبار',
                style: TextStyle(color: Colors.white.withValues(alpha: 0.95), fontSize: 14, height: 1.35),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        const Text('اليوم', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(child: _KpiTile(value: '${s.todayExamined}', label: 'مختبر', color: const Color(0xFF2563EB))),
            const SizedBox(width: 8),
            Expanded(
              child: _KpiTile(
                value: s.todayAvgScore != null ? '${s.todayAvgScore}' : '—',
                label: 'متوسط',
                color: const Color(0xFF059669),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _KpiTile(
                value: s.todayHighestScore != null ? '${s.todayHighestScore}' : '—',
                label: 'أعلى',
                color: const Color(0xFF7C3AED),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _KpiTile(
                value: s.todayLowestScore != null ? '${s.todayLowestScore}' : '—',
                label: 'أدنى',
                color: const Color(0xFFD97706),
              ),
            ),
          ],
        ),
        const SizedBox(height: 20),
        const Text('الإجمالي', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(child: _WideStat(label: 'مكتمل', value: '${s.completed}', border: const Color(0xFF059669))),
            const SizedBox(width: 12),
            Expanded(child: _WideStat(label: 'معلق', value: '${s.pending}', border: const Color(0xFFD97706))),
          ],
        ),
        const SizedBox(height: 24),
        _ActionRow(
          icon: Icons.add_circle_outline_rounded,
          title: 'بدء اختبار جديد',
          subtitle: 'اختر الاختبار ثم الطالب وأدخل الدرجات',
          onTap: () {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const ExamTestStepperPage()));
          },
        ),
        const SizedBox(height: 10),
        _ActionRow(
          icon: Icons.summarize_rounded,
          title: 'ملخص اليوم',
          subtitle: 'توزيع الدرجات وقائمة النتائج',
          onTap: () {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const ExamDailySummaryPage()));
          },
        ),
        const SizedBox(height: 24),
        Text(
          'صلاحياتك: عرض وإدخال نتائج الاختبارات ضمن نطاق مراكزك.',
          style: TextStyle(color: Colors.grey.shade700, fontSize: 13, height: 1.4),
        ),
      ],
    );
  }
}

// ----------------------------------------------------------------------
// الويدجتس المساعدة (لم يطرأ عليها أي تغيير منطقي)
// ----------------------------------------------------------------------

class _KpiTile extends StatelessWidget {
  final String value;
  final String label;
  final Color color;

  const _KpiTile({required this.value, required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
            child: Icon(Icons.analytics_rounded, color: color, size: 24),
          ),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        ],
      ),
    );
  }
}

class _WideStat extends StatelessWidget {
  final String label;
  final String value;
  final Color border;

  const _WideStat({required this.label, required this.value, required this.border});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border.withValues(alpha: 0.35)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey.shade800)),
          Text(value, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: border)),
        ],
      ),
    );
  }
}

class _ActionRow extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _ActionRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Row(
            children: [
              Icon(icon, color: _gradientStart, size: 26),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    const SizedBox(height: 4),
                    Text(subtitle, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                  ],
                ),
              ),
              Icon(Icons.chevron_left_rounded, color: Colors.grey.shade400),
            ],
          ),
        ),
      ),
    );
  }
}