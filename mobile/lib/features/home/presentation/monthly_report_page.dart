import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_error_view.dart' show AppErrorView, AppLoadingView;
import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/teacher_daily_repository.dart';
import 'bloc/monthly_report_cubit.dart';

class MonthlyReportPage extends StatelessWidget {
  final TeacherDailyRepository? repository;
  const MonthlyReportPage({super.key, this.repository});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => MonthlyReportCubit(
        repository ?? TeacherDailyRepositoryImpl(apiClient: ApiClient(tokenStorage: SecureTokenStorage())),
      )..loadReport('${DateTime.now().year}-${DateTime.now().month.toString().padLeft(2, '0')}'),
      child: const _MonthlyReportView(),
    );
  }
}

class _MonthlyReportView extends StatelessWidget {
  const _MonthlyReportView();

  Future<void> _pickMonth(BuildContext context) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year, now.month, 1),
      firstDate: DateTime(now.year - 2, 1, 1),
      lastDate: DateTime(now.year + 1, 12, 31),
    );
    if (picked != null && context.mounted) {
      final monthStr = '${picked.year}-${picked.month.toString().padLeft(2, '0')}';
      context.read<MonthlyReportCubit>().loadReport(monthStr);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('تقرير الشهر'),
        actions: [IconButton(icon: const Icon(Icons.calendar_month_rounded), onPressed: () => _pickMonth(context))],
      ),
      body: BlocBuilder<MonthlyReportCubit, MonthlyReportState>(
        builder: (context, state) {
          if (state is MonthlyReportLoading) return const AppLoadingView(message: 'جاري تحميل الإحصاءات...');
          if (state is MonthlyReportError) return AppErrorView(message: state.message, onRetry: () => context.read<MonthlyReportCubit>().loadReport('${DateTime.now().year}-${DateTime.now().month.toString().padLeft(2, '0')}'));
          
          final loaded = state as MonthlyReportLoaded;
          return RefreshIndicator(
            color: AppColors.forest,
            onRefresh: () => context.read<MonthlyReportCubit>().loadReport(loaded.month),
            child: _ReportContent(report: loaded.report, month: loaded.month),
          );
        },
      ),
    );
  }
}

class _ReportContent extends StatelessWidget {
  final MonthlyReportDto report;
  final String month;
  const _ReportContent({required this.report, required this.month});

  String _monthLabel() {
    const months = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    final parts = month.split('-');
    final m = int.parse(parts[1]);
    return m >= 1 && m <= 12 ? '${months[m]} ${parts[0]}' : month;
  }

  @override
  Widget build(BuildContext context) {
    int a(String k) => report.attendance[k] ?? 0;
    int e(String k) => report.evaluations[k] ?? 0;

    final attTotal = a('present') + a('excused_absence') + a('unexcused_absence');
    final attValues = [a('present').toDouble(), a('excused_absence').toDouble(), a('unexcused_absence').toDouble()];
    final attLabels = ['حاضر', 'غياب مبرر', 'غياب غير مبرر'];
    final attColors = [AppColors.mint, Colors.orange, Colors.redAccent];

    final evalSum = e('excellent') + e('good') + e('needs_improvement');
    final evalValues = [e('excellent').toDouble(), e('good').toDouble(), e('needs_improvement').toDouble()];
    final maxEval = evalValues.isEmpty ? 4.0 : evalValues.reduce((a, b) => a > b ? a : b);
    final evalColors = [AppColors.forest, Colors.blue, Colors.deepOrange];
    final evalLabels = ['متميز', 'جيد', 'مقصر'];

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      children: [
        Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: AppColors.forest.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(16)), child: Row(children: [const Icon(Icons.date_range_rounded, color: AppColors.forest), const SizedBox(width: 10), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('الفترة', style: TextStyle(fontSize: 12)), Text(_monthLabel(), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17))]))])),
        
        const SizedBox(height: 20),
        const Text('الحضور', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        if (attTotal > 0) SizedBox(height: 200, child: Row(children: [Expanded(flex: 2, child: PieChart(PieChartData(sections: List.generate(3, (i) => PieChartSectionData(color: attColors[i], value: attValues[i], title: attValues[i] > 0 ? '${(attValues[i]/attTotal*100).toStringAsFixed(0)}%' : '', radius: 52))))), Expanded(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: List.generate(3, (i) => Text('${attLabels[i]}: ${attValues[i].toInt()}'))))])) else const Text('لا توجد بيانات حضور'),
        
        const SizedBox(height: 24),
        const Text('التقييم', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        if (evalSum > 0) SizedBox(height: 220, child: BarChart(BarChartData(maxY: maxEval * 1.25, titlesData: FlTitlesData(bottomTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, getTitlesWidget: (v, m) => Text(evalLabels[v.toInt()])))), barGroups: List.generate(3, (i) => BarChartGroupData(x: i, barRods: [BarChartRodData(toY: evalValues[i], color: evalColors[i], width: 22)]))))) else const Text('لا توجد بيانات تقييم'),
        
        const SizedBox(height: 24),
        const Text('أكثر الأسباب تكراراً', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        ...report.reasons.take(12).map((r) => Card(child: ListTile(title: Text(r['label']?.toString() ?? ''), trailing: Text('${r['total']}')))),
      ],
    );
  }
}