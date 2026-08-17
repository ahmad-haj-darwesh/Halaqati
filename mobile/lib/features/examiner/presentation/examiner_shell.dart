import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../pages/exam_daily_summary_page.dart';
import 'examiner_home_page.dart';
import 'examiner_list_pages.dart';
import 'bloc/examiner_shell_cubit.dart'; // مسار الـ Cubit

/// هيكل المختبر مع شريط تنقّل سفلي لجميع الشاشات بنظام Cubit.
///
/// Arabic: يجمع شاشات المختبر في `IndexedStack` للحفاظ على حالة كل تبويب.
/// EN: Bottom-nav shell that keeps tab state using IndexedStack and Cubit.
class ExaminerShellPage extends StatelessWidget {
  const ExaminerShellPage({super.key});

  @override
  Widget build(BuildContext context) {
    // توفير الـ Cubit على مستوى الهيكل
    return BlocProvider(
      create: (_) => ExaminerShellCubit(),
      child: const _ExaminerShellView(),
    );
  }
}

class _ExaminerShellView extends StatelessWidget {
  const _ExaminerShellView();

  @override
  Widget build(BuildContext context) {
    // الاستماع لتغيرات الـ Index لبناء الواجهة
    return BlocBuilder<ExaminerShellCubit, int>(
      builder: (context, currentIndex) {
        return Scaffold(
          body: IndexedStack(
            index: currentIndex,
            children: const [
              ExaminerHomePage(),
              ExaminerTestsListPage(),
              ExaminerAssignmentsListPage(),
              ExaminerResultsListPage(),
              ExamDailySummaryPage(),
            ],
          ),
          bottomNavigationBar: NavigationBar(
            selectedIndex: currentIndex,
            onDestinationSelected: (i) => context.read<ExaminerShellCubit>().changeTab(i),
            destinations: const [
              NavigationDestination(icon: Icon(Icons.dashboard_rounded), label: 'الرئيسية'),
              NavigationDestination(icon: Icon(Icons.quiz_rounded), label: 'الاختبارات'),
              NavigationDestination(icon: Icon(Icons.assignment_rounded), label: 'التعيينات'),
              NavigationDestination(icon: Icon(Icons.fact_check_rounded), label: 'النتائج'),
              NavigationDestination(icon: Icon(Icons.summarize_rounded), label: 'ملخص اليوم'),
            ],
          ),
        );
      },
    );
  }
}