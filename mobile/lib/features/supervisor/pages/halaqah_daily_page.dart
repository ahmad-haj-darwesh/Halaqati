import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import '../data/supervisor_models.dart';
import '../data/supervisor_repository.dart';
import '../presentation/bloc/halaqah_daily_cubit.dart'; // مسار الكيوبت

const _g0 = Color(0xFF2563EB);

/// نقطة الإدخال وحقن الكيوبت (Stateless)
class HalaqahDailyPage extends StatelessWidget {
  const HalaqahDailyPage({
    super.key,
    required this.halaqahId,
    required this.halaqahName,
  });

  final int halaqahId;
  final String halaqahName;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final repo = sl<SupervisorRepository>();
        return HalaqahDailyCubit(repo: repo, halaqahId: halaqahId)..loadDailyData();
      },
      child: _HalaqahDailyView(halaqahName: halaqahName),
    );
  }
}

/// واجهة المستخدم المستقلة (Stateless)
class _HalaqahDailyView extends StatelessWidget {
  final String halaqahName;
  
  const _HalaqahDailyView({required this.halaqahName});

  Future<void> _pickDate(BuildContext context, DateTime currentDate) async {
    final d = await showDatePicker(
      context: context,
      initialDate: currentDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (d != null && context.mounted) {
      context.read<HalaqahDailyCubit>().changeDate(d);
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<HalaqahDailyCubit, HalaqahDailyState>(
      builder: (context, state) {
        return Scaffold(
          appBar: AppBar(
            title: Text(halaqahName),
            actions: [
              IconButton(
                icon: const Icon(Icons.calendar_month_rounded),
                onPressed: () => _pickDate(context, state.date),
              ),
            ],
          ),
          body: RefreshIndicator(
            color: _g0,
            onRefresh: () => context.read<HalaqahDailyCubit>().loadDailyData(),
            child: _buildBody(context, state),
          ),
        );
      },
    );
  }

  Widget _buildBody(BuildContext context, HalaqahDailyState state) {
    if (state is HalaqahDailyLoading) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator(color: _g0)),
        ],
      );
    }
    
    if (state is HalaqahDailyError) {
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
              onPressed: () => context.read<HalaqahDailyCubit>().loadDailyData(),
              child: const Text('إعادة المحاولة'),
            ),
          ),
        ],
      );
    }

    if (state is HalaqahDailyLoaded) {
      final d = state.data;
      final s = d.summary;
      final total = s['total'] as int? ?? 0;
      final present = s['present'] as int? ?? 0;
      final rate = s['attendance_rate'] as int? ?? 0;
      final allNotRecorded = total > 0 && d.records.every((r) => r.attendanceStatus == 'not_recorded');

      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          InkWell(
            onTap: () => _pickDate(context, state.date),
            borderRadius: BorderRadius.circular(12),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(
                children: [
                  const Icon(Icons.event_rounded, color: _g0),
                  const SizedBox(width: 8),
                  Text(
                    DateFormat('dd/MM/yyyy').format(DateTime.parse(d.date)),
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text('المعلّم: ${d.teacherName ?? '—'}', style: TextStyle(color: Colors.grey.shade800)),
          Text('المركز: ${d.centerName}', style: TextStyle(color: Colors.grey.shade700)),
          const SizedBox(height: 16),
          const Text('ملخص الحضور', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(child: _sumChip('$present', 'حاضر', Colors.green.shade700)),
              const SizedBox(width: 6),
              Expanded(child: _sumChip('${s['absent_excused'] ?? 0}', 'مبرر', Colors.orange.shade800)),
              const SizedBox(width: 6),
              Expanded(child: _sumChip('${s['absent_unexcused'] ?? 0}', 'غير مبرر', Colors.red.shade700)),
              const SizedBox(width: 6),
              Expanded(child: _sumChip('${s['not_recorded'] ?? 0}', '؟', Colors.grey.shade600)),
            ],
          ),
          const SizedBox(height: 12),
          Text('نسبة الحضور: $rate٪'),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: total > 0 ? present / total : 0,
              minHeight: 10,
              backgroundColor: Colors.grey.shade200,
              color: _g0,
            ),
          ),
          if (allNotRecorded) ...[
            const SizedBox(height: 16),
            Card(
              color: Colors.blue.shade50,
              child: const Padding(
                padding: EdgeInsets.all(16),
                child: Text('لم يسجّل المعلّم الحضور بعد'),
              ),
            ),
          ],
          const SizedBox(height: 20),
          const Text('قائمة الطلاب', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          const SizedBox(height: 8),
          ...d.records.map(_studentTile),
        ],
      );
    }
    
    return const SizedBox();
  }

  Widget _sumChip(String v, String l, Color c) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Text(v, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: c)),
          Text(l, style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
        ],
      ),
    );
  }

  Widget _studentTile(StudentRecord r) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(r.studentName, style: const TextStyle(fontWeight: FontWeight.w700))),
                _statusBadge(r.attendanceStatus),
              ],
            ),
            if (r.performanceStatus != null && r.performanceStatus!.isNotEmpty) ...[
              const SizedBox(height: 6),
              _perfChip(r.performanceStatus!),
            ],
            if (r.memorizationFrom != null && r.memorizationTo != null && r.memorizationFrom!.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                'الحفظ: من ${r.memorizationFrom} إلى ${r.memorizationTo}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
              ),
            ],
            if (r.notes != null && r.notes!.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(r.notes!, style: TextStyle(fontSize: 12, color: Colors.grey.shade800)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _statusBadge(String status) {
    late String label;
    late Color bg;
    late Color fg;
    switch (status) {
      case 'present':
        label = 'حاضر';
        bg = Colors.green.shade50;
        fg = Colors.green.shade800;
        break;
      case 'absent_excused':
        label = 'غياب مبرر';
        bg = Colors.orange.shade50;
        fg = Colors.orange.shade900;
        break;
      case 'absent_unexcused':
        label = 'غياب';
        bg = Colors.red.shade50;
        fg = Colors.red.shade800;
        break;
      default:
        label = 'غير مسجّل';
        bg = Colors.grey.shade200;
        fg = Colors.grey.shade800;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(label, style: TextStyle(fontSize: 12, color: fg, fontWeight: FontWeight.w600)),
    );
  }

  Widget _perfChip(String key) {
    String label = key;
    Color c = Colors.teal.shade800;
    if (key == 'excellent_homework') {
      label = 'متميز في الواجب';
      c = Colors.green.shade800;
    } else if (key == 'excellent_behavior') {
      label = 'مؤدب ومتميز';
      c = Colors.teal.shade800;
    } else if (key == 'weak_homework') {
      label = 'تقصير في الواجب';
      c = Colors.orange.shade900;
    } else if (key == 'bad_behavior') {
      label = 'سوء سلوك';
      c = Colors.red.shade800;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: c.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(label, style: TextStyle(fontSize: 11, color: c)),
    );
  }
}