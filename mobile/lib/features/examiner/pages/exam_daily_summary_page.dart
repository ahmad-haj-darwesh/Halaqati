import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:share_plus/share_plus.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/examiner_models.dart';
import '../data/examiner_repository.dart';
import '../presentation/bloc/exam_daily_summary_cubit.dart';

/// شاشة ملخص اليوم للمختبر بنظام Cubit.
class ExamDailySummaryPage extends StatelessWidget {
  const ExamDailySummaryPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = ExaminerRepositoryImpl(apiClient: apiClient);
        return ExamDailySummaryCubit(repo)..loadSummary();
      },
      child: const _ExamDailySummaryView(),
    );
  }
}

class _ExamDailySummaryView extends StatelessWidget {
  const _ExamDailySummaryView();

  Future<void> _pickDate(BuildContext context, DateTime currentDate) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: currentDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null && context.mounted) {
      context.read<ExamDailySummaryCubit>().changeDate(picked);
    }
  }

  void _share(DailySummary s) {
    final buf = StringBuffer()
      ..writeln('ملخص اختبارات — ${s.date}')
      ..writeln('عدد المختبرين: ${s.totalExamined}')
      ..writeln('المتوسط: ${s.avgScore}')
      ..writeln('أعلى: ${s.highestScore} · أدنى: ${s.lowestScore}')
      ..writeln('التوزيع:')
      ..writeln(s.distribution.entries.map((e) => '${e.key}: ${e.value}').join('\n'));
    Share.share(buf.toString());
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ExamDailySummaryCubit, ExamDailySummaryState>(
      builder: (context, state) {
        final currentDate = state is ExamDailySummaryLoaded
            ? state.date
            : (state is ExamDailySummaryError ? state.date : DateTime.now());

        return Scaffold(
          appBar: AppBar(
            title: const Text('ملخص اليوم'),
            actions: [
              IconButton(
                icon: const Icon(Icons.calendar_month_rounded),
                onPressed: () => _pickDate(context, currentDate),
              ),
            ],
          ),
          body: RefreshIndicator(
            color: const Color(0xFF2563EB),
            onRefresh: () => context.read<ExamDailySummaryCubit>().loadSummary(),
            child: _buildBody(context, state),
          ),
        );
      },
    );
  }

  Widget _buildBody(BuildContext context, ExamDailySummaryState state) {
    if (state is ExamDailySummaryLoading) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator(color: Color(0xFF2563EB))),
        ],
      );
    }

    if (state is ExamDailySummaryError) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 80),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Text(state.message, textAlign: TextAlign.center),
          ),
          Center(
            child: TextButton(
              onPressed: () => context.read<ExamDailySummaryCubit>().loadSummary(),
              child: const Text('إعادة المحاولة'),
            ),
          ),
        ],
      );
    }

    if (state is ExamDailySummaryLoaded) {
      final s = state.summary;
      if (s.totalExamined == 0 && s.results.isEmpty) {
        return ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            const SizedBox(height: 80),
            Icon(Icons.inbox_rounded, size: 72, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Center(
              child: Text('لا توجد اختبارات في هذا اليوم', style: TextStyle(color: Colors.grey.shade600)),
            ),
          ],
        );
      }

      final dist = s.distribution;
      final maxC = dist.values.isEmpty ? 1 : dist.values.reduce((a, b) => a > b ? a : b);
      final dateStr =
          '${state.date.year.toString().padLeft(4, '0')}-${state.date.month.toString().padLeft(2, '0')}-${state.date.day.toString().padLeft(2, '0')}';

      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          InkWell(
            onTap: () => _pickDate(context, state.date),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(dateStr, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(width: 8),
                const Icon(Icons.edit_calendar_rounded, size: 20),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(child: _MiniKpi(v: '${s.totalExamined}', l: 'مختبر')),
              const SizedBox(width: 8),
              Expanded(child: _MiniKpi(v: s.avgScore.toStringAsFixed(1), l: 'متوسط')),
              const SizedBox(width: 8),
              Expanded(child: _MiniKpi(v: '${s.highestScore}', l: 'أعلى')),
            ],
          ),
          const SizedBox(height: 24),
          const Text('توزيع الدرجات', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 12),
          ...dist.entries.map((e) => _DistBar(label: e.key, count: e.value, max: maxC)),
          const SizedBox(height: 24),
          const Text('قائمة النتائج', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          ...s.results.map(
            (r) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                title: Text(r['student_name']?.toString() ?? ''),
                subtitle: Text(r['tested_surah']?.toString() ?? ''),
                trailing: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text('${r['total_score']}', style: const TextStyle(fontWeight: FontWeight.bold)),
                    Text(
                      r['level_ar']?.toString() ?? '',
                      style: const TextStyle(fontSize: 12),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: () => _share(s),
            icon: const Icon(Icons.share_rounded),
            label: const Text('مشاركة الملخص'),
          ),
        ],
      );
    }

    return const SizedBox();
  }
}

class _MiniKpi extends StatelessWidget {
  final String v;
  final String l;

  const _MiniKpi({required this.v, required this.l});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Text(v, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
          Text(l, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        ],
      ),
    );
  }
}

class _DistBar extends StatelessWidget {
  final String label;
  final int count;
  final int max;

  const _DistBar({required this.label, required this.count, required this.max});

  Color get _c {
    if (label == 'ممتاز') return const Color(0xFF059669);
    if (label == 'جيد جداً') return const Color(0xFF0D9488);
    if (label == 'جيد') return const Color(0xFF2563EB);
    if (label == 'مقبول') return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  @override
  Widget build(BuildContext context) {
    final frac = max > 0 ? count / max : 0.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          SizedBox(width: 72, child: Text(label, style: const TextStyle(fontSize: 13))),
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: LinearProgressIndicator(
                value: frac,
                minHeight: 14,
                backgroundColor: Colors.grey.shade200,
                color: _c,
              ),
            ),
          ),
          const SizedBox(width: 8),
          SizedBox(width: 28, child: Text('$count', textAlign: TextAlign.end)),
        ],
      ),
    );
  }
}