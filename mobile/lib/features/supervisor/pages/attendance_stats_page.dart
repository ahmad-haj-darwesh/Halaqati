import 'package:flutter/material.dart';
import '../../../core/utils/arabic_plural.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/supervisor_models.dart';
import '../data/supervisor_repository.dart';
import '../pages/halaqah_daily_page.dart';
import '../presentation/bloc/attendance_stats_cubit.dart'; // مسار الكيوبت

const _g0 = Color(0xFF2563EB);

/// نقطة الإدخال وحقن الكيوبت (Stateless)
class AttendanceStatsPage extends StatelessWidget {
  const AttendanceStatsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = SupervisorRepositoryImpl(apiClient: apiClient);
        return AttendanceStatsCubit(repo)..loadStats();
      },
      child: const _AttendanceStatsView(),
    );
  }
}

/// واجهة المستخدم التي تدير الأنيميشن (Stateful)
class _AttendanceStatsView extends StatefulWidget {
  const _AttendanceStatsView();

  @override
  State<_AttendanceStatsView> createState() => _AttendanceStatsViewState();
}

class _AttendanceStatsViewState extends State<_AttendanceStatsView> with SingleTickerProviderStateMixin {
  late final AnimationController _anim;

  @override
  void initState() {
    super.initState();
    // إعداد متحكم الرسوم المتحركة
    _anim = AnimationController(vsync: this, duration: const Duration(milliseconds: 700));
  }

  @override
  void dispose() {
    _anim.dispose();
    super.dispose();
  }

  Color _rateColor(int r) {
    if (r >= 80) return const Color(0xFF059669);
    if (r >= 60) return _g0;
    if (r >= 40) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  Color _overallColor(double r) {
    if (r >= 80) return const Color(0xFF059669);
    if (r >= 60) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إحصائيات الحضور')),
      body: RefreshIndicator(
        color: _g0,
        onRefresh: () => context.read<AttendanceStatsCubit>().loadStats(),
        child: BlocConsumer<AttendanceStatsCubit, AttendanceStatsState>(
          listener: (context, state) {
            // إعادة تشغيل الأنيميشن من الصفر كلما تم تحميل بيانات جديدة بنجاح
            if (state is AttendanceStatsLoaded) {
              _anim.forward(from: 0);
            }
          },
          builder: (context, state) {
            return _buildBody(context, state);
          },
        ),
      ),
    );
  }

  Widget _buildBody(BuildContext context, AttendanceStatsState state) {
    if (state is AttendanceStatsLoading) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator(color: _g0)),
        ],
      );
    }
    
    if (state is AttendanceStatsError) {
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
              onPressed: () => context.read<AttendanceStatsCubit>().loadStats(),
              child: const Text('إعادة المحاولة'),
            ),
          ),
        ],
      );
    }

    if (state is AttendanceStatsLoaded) {
      final s = state.stats;
      
      if (s.halaqahs.isEmpty) {
        return ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: const [
            SizedBox(height: 80),
            Padding(
              padding: EdgeInsets.all(24),
              child: Text('لا توجد بيانات حضور خلال هذه الفترة', textAlign: TextAlign.center),
            ),
          ],
        );
      }

      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              _chip(context, 7, '7 أيام', state.days),
              const SizedBox(width: 8),
              _chip(context, 14, '14 يوماً', state.days),
              const SizedBox(width: 8),
              _chip(context, 30, '30 يوماً', state.days),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              children: [
                Text(
                  '${s.overallRate.toStringAsFixed(1)}٪',
                  style: TextStyle(
                    color: _overallColor(s.overallRate),
                    fontSize: 36,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'متوسط الحضور الإجمالي',
                  style: TextStyle(color: Colors.grey.shade800, fontSize: 14),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          ...s.halaqahs.map((h) => _HalaqahRow(
                h: h,
                anim: _anim,
                barColor: _rateColor(h.attendanceRate),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute<void>(
                      builder: (_) => HalaqahDailyPage(
                        halaqahId: h.halaqahId,
                        halaqahName: h.halaqahName,
                      ),
                    ),
                  );
                },
              )),
        ],
      );
    }

    return const SizedBox();
  }

  Widget _chip(BuildContext context, int days, String label, int currentSelectedDays) {
    final sel = currentSelectedDays == days;
    return Expanded(
      child: ChoiceChip(
        label: Text(label, textAlign: TextAlign.center),
        selected: sel,
        onSelected: (_) => context.read<AttendanceStatsCubit>().changeDays(days),
        selectedColor: _g0,
        labelStyle: TextStyle(color: sel ? Colors.white : Colors.grey.shade800),
        checkmarkColor: Colors.white,
        showCheckmark: false, // لإبقاء التصميم نظيفاً بدون علامة الصح الافتراضية
      ),
    );
  }
}

class _HalaqahRow extends StatelessWidget {
  const _HalaqahRow({
    required this.h,
    required this.anim,
    required this.barColor,
    required this.onTap,
  });

  final HalaqahAttendance h;
  final AnimationController anim;
  final Color barColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(child: Text(h.halaqahName, style: const TextStyle(fontWeight: FontWeight.w700))),
                  Text('${h.attendanceRate}٪', style: TextStyle(fontWeight: FontWeight.w800, color: barColor)),
                ],
              ),
              const SizedBox(height: 8),
              AnimatedBuilder(
                animation: anim,
                builder: (_, __) {
                  final t = Curves.easeOutCubic.transform(anim.value);
                  return ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: t * (h.attendanceRate / 100).clamp(0.0, 1.0),
                      minHeight: 8,
                      backgroundColor: Colors.grey.shade200,
                      color: barColor,
                    ),
                  );
                },
              ),
              const SizedBox(height: 6),
              Text(
                'حضور مسجَّل: ${h.presentCount} من ${ArCount.records(h.totalRecords)}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
              ),
            ],
          ),
        ),
      ),
    );
  }
}