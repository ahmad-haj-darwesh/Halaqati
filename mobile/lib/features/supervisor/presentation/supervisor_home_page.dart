import 'package:flutter/material.dart';
import '../../../core/utils/arabic_plural.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../../../widgets/notification_bell_button.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/presentation/login_page.dart';
import '../data/supervisor_repository.dart';
import '../pages/attendance_stats_page.dart';
import '../pages/my_visits_page.dart';
import '../pages/supervisory_visit_form_page.dart';
import 'supervisor_list_pages.dart';
import 'bloc/supervisor_home_cubit.dart'; // مسار الـ Cubit الجديد

const _gradientStart = Color(0xFF2563EB);
const _gradientEnd = Color(0xFF1D4ED8);

/// الصفحة الرئيسية للمشرف (Supervisor Dashboard) بنظام Cubit.
class SupervisorHomePage extends StatelessWidget {
  const SupervisorHomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = SupervisorRepositoryImpl(apiClient: apiClient);
        return SupervisorHomeCubit(repo)..loadDashboard();
      },
      child: const _SupervisorHomeView(),
    );
  }
}

class _SupervisorHomeView extends StatelessWidget {
  const _SupervisorHomeView();

  /// تأكيد تسجيل الخروج ثم إعادة توجيه المستخدم لشاشة الدخول.
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
      MaterialPageRoute<void>(builder: (_) => const LoginPage()),
    );
  }

  /// التنقل لشاشة أخرى مع خيار إعادة تحميل الإحصاءات عند العودة.
  Future<void> _open(BuildContext context, Widget page, {bool reloadStatsAfter = false}) async {
    await Navigator.push(context, MaterialPageRoute<void>(builder: (_) => page));
    if (reloadStatsAfter && context.mounted) {
      context.read<SupervisorHomeCubit>().loadDashboard();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F4FA),
      appBar: AppBar(
        title: const Text('لوحة المشرف'),
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
        onRefresh: () => context.read<SupervisorHomeCubit>().loadDashboard(),
        child: BlocBuilder<SupervisorHomeCubit, SupervisorHomeState>(
          builder: (context, state) {
            if (state is SupervisorHomeLoading) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator(color: _gradientStart)),
                ],
              );
            }
            
            if (state is SupervisorHomeError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  const SizedBox(height: 80),
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(state.message, textAlign: TextAlign.center),
                  ),
                  Center(
                    child: FilledButton(
                      onPressed: () => context.read<SupervisorHomeCubit>().loadDashboard(),
                      child: const Text('إعادة المحاولة'),
                    ),
                  ),
                ],
              );
            }

            if (state is SupervisorHomeLoaded) {
              return _buildDashboardContent(context, state);
            }

            return const SizedBox();
          },
        ),
      ),
    );
  }

  Widget _buildDashboardContent(BuildContext context, SupervisorHomeLoaded state) {
    final s = state.stats;
    final name = state.supervisorName;
    final nCenters = state.managedCount ?? s.centersCount;

    final avgTriple = (s.avgTeachingScore + s.avgPlanScore + s.avgEngagement) / 3.0;
    final attendanceStr = s.attendanceRate7d != null ? '${s.attendanceRate7d!.toStringAsFixed(0)}٪' : '—';

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
              Text(
                'مرحباً $name',
                style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(
                'مسؤول عن $nCenters مراكز',
                style: TextStyle(color: Colors.white.withValues(alpha: 0.95), fontSize: 14, height: 1.35),
              ),
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: _gradientStart,
                  ),
                  onPressed: () => _open(context, const SupervisoryVisitFormPage(), reloadStatsAfter: true),
                  child: const Text('تسجيل زيارة'),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        const Text('هذا الشهر', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _KpiTile(
                value: '${s.visitsThisMonth}',
                label: 'زيارات',
                color: _gradientStart,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _KpiTile(
                value: avgTriple.toStringAsFixed(1),
                label: 'متوسط',
                color: const Color(0xFF059669),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _KpiTile(
                value: attendanceStr,
                label: 'حضور 7ي',
                color: const Color(0xFF7C3AED),
              ),
            ),
          ],
        ),
        if (s.unvisitedHalaqahs > 0) ...[
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.warning_amber_rounded, color: Colors.orange.shade800),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    '${ArCount.halaqahs(s.unvisitedHalaqahs)} لم تُزر منذ 30 يوماً',
                    style: TextStyle(color: Colors.orange.shade900, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 20),
        const Text('الإجراءات السريعة', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        _QuickAction(
          icon: Icons.add_circle_outline_rounded,
          title: 'تسجيل زيارة إشرافية',
          subtitle: 'اختر المركز والمعلّم والدرجات',
          onTap: () => _open(context, const SupervisoryVisitFormPage(), reloadStatsAfter: true),
        ),
        const SizedBox(height: 8),
        _QuickAction(
          icon: Icons.visibility_rounded,
          title: 'متابعة الحضور اليومي',
          subtitle: 'عرض حلقاتك وسجلات الطلاب',
          onTap: () => _open(context, const SupervisorHalaqahsListPage()),
        ),
        const SizedBox(height: 8),
        _QuickAction(
          icon: Icons.bar_chart_rounded,
          title: 'إحصائيات الحضور',
          subtitle: 'مقارنة نسب الحضور بين الحلقات',
          onTap: () => _open(context, const AttendanceStatsPage()),
        ),
        const SizedBox(height: 8),
        _QuickAction(
          icon: Icons.history_rounded,
          title: 'سجل زياراتي',
          subtitle: 'آخر الزيارات المسجّلة',
          onTap: () => _open(context, const MyVisitsPage(), reloadStatsAfter: true),
        ),
        const SizedBox(height: 8),
        _QuickAction(
          icon: Icons.school_rounded,
          title: 'المعلّمون',
          subtitle: 'تفاصيل وتقييمات',
          onTap: () => _open(context, const SupervisorTeachersListPage()),
        ),
        const SizedBox(height: 8),
        _QuickAction(
          icon: Icons.apartment_rounded,
          title: 'المراكز',
          subtitle: 'قائمة المراكز الإشرافية',
          onTap: () => _open(context, const SupervisorCentersListPage()),
        ),
      ],
    );
  }
}

class _KpiTile extends StatelessWidget {
  const _KpiTile({required this.value, required this.label, required this.color});

  final String value;
  final String label;
  final Color color;

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
          Text(value, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 17, color: color)),
          const SizedBox(height: 4),
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

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