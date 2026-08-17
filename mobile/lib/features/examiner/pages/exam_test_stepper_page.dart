import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/examiner_models.dart';
import '../data/examiner_repository.dart';
import '../widgets/student_detail_bottom_sheet.dart';
import '../presentation/bloc/exam_test_stepper_cubit.dart';

int _computeTotal(int m, int t, int r) =>
    ((m * 0.5) + (t * 0.3) + (r * 0.2)).round();

/// خطوة إدخال نتيجة اختبار للمختبر بنظام Cubit.
class ExamTestStepperPage extends StatelessWidget {
  const ExamTestStepperPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = ExaminerRepositoryImpl(apiClient: apiClient);
        return ExamTestStepperCubit(repo)..loadTests();
      },
      child: const _ExamTestStepperView(),
    );
  }
}

class _ExamTestStepperView extends StatefulWidget {
  const _ExamTestStepperView();

  @override
  State<_ExamTestStepperView> createState() => _ExamTestStepperViewState();
}

class _ExamTestStepperViewState extends State<_ExamTestStepperView> {
  final PageController _page = PageController();
  final _surahCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();

  @override
  void dispose() {
    _page.dispose();
    _surahCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  void _showErrorSheet(BuildContext context, String message) {
    showModalBottomSheet<void>(
      context: context,
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('حسناً'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openStudentSheet(
    BuildContext context,
    Map<String, dynamic> row,
  ) async {
    final sid = (row['student_id'] as num).toInt();
    final cubit = context.read<ExamTestStepperCubit>();
    final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
    final repo = ExaminerRepositoryImpl(apiClient: apiClient);

    await showExaminerStudentDetailSheet(
      context: context,
      repo: repo,
      studentId: sid,
      onConfirm: () {
        cubit.selectAssignment(row);
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<ExamTestStepperCubit, ExamTestStepperState>(
      listener: (context, state) {
        if (state is StepperErrorNotification) {
          _showErrorSheet(context, state.message);
        }
        if (state is StepperDataState) {
          if (_page.hasClients && _page.page?.round() != state.step) {
            _page.jumpToPage(state.step);
          }
        }
      },
      builder: (context, state) {
        StepperDataState dataState = StepperDataState();
        if (state is StepperDataState) dataState = state;
        if (state is StepperErrorNotification) dataState = state.currentState;

        if (dataState.success && dataState.submitResponse != null) {
          final r = dataState.submitResponse!;
          return Scaffold(
            appBar: AppBar(title: const Text('تم الحفظ')),
            body: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.check_circle_rounded,
                      color: Color(0xFF059669),
                      size: 88,
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'تم حفظ النتيجة بنجاح',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'المجموع: ${r.totalScore} — ${r.level}',
                      style: const TextStyle(fontSize: 18),
                    ),
                    const SizedBox(height: 32),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () {
                              _surahCtrl.clear();
                              _notesCtrl.clear();
                              context
                                  .read<ExamTestStepperCubit>()
                                  .resetForAnother();
                            },
                            child: const Text('اختبار طالب آخر'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: FilledButton(
                            onPressed: () => Navigator.pop(context),
                            child: const Text('العودة للرئيسية'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        }

        return Scaffold(
          appBar: AppBar(
            title: const Text('إدخال نتيجة اختبار'),
            leading: IconButton(
              icon: Icon(
                dataState.step == 0
                    ? Icons.close_rounded
                    : Icons.arrow_back_rounded,
              ),
              onPressed: () {
                if (dataState.step == 0) {
                  Navigator.pop(context);
                } else {
                  context.read<ExamTestStepperCubit>().goToStep(
                    dataState.step - 1,
                  );
                }
              },
            ),
          ),
          body: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  vertical: 12,
                  horizontal: 16,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(4, (i) {
                    final active = i == dataState.step;
                    final done = i < dataState.step;
                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: done || active
                              ? const Color(0xFF2563EB)
                              : Colors.grey.shade300,
                        ),
                      ),
                    );
                  }),
                ),
              ),
              Expanded(
                child: PageView(
                  controller: _page,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    _step1Tests(context, dataState),
                    _step2Students(context, dataState),
                    _step3Scores(context, dataState),
                    _step4Review(context, dataState),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _step1Tests(BuildContext context, StepperDataState state) {
    if (state.loadingTests) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF2563EB)),
      );
    }
    if (state.testsError != null) {
      return Center(child: Text(state.testsError!));
    }
    if (state.tests.isEmpty) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Icon(Icons.quiz_outlined, size: 56, color: Colors.grey.shade400),
          const SizedBox(height: 16),
          Text(
            'لا توجد اختبارات ضمن نطاقك',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey.shade800,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            'يُعرض الاختبار إذا كان نطاقه (مركز أو حلقة أو منطقة) يطابق مراكزك كمختبر، أو إذا وُجدت تعيينات طلاب لهذا الاختبار في حلقات تلك المراكز.',
            style: TextStyle(
              fontSize: 14,
              height: 1.45,
              color: Colors.grey.shade700,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            'تحقق من لوحة الإدارة: ربط حسابك كمسؤول عن المركز (حقل مشرف المركز)، وإعداد نطاق الاختبار أو إنشاء تعيينات للطلاب.',
            style: TextStyle(
              fontSize: 13,
              height: 1.4,
              color: Colors.grey.shade600,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          Center(
            child: TextButton.icon(
              onPressed: () => context.read<ExamTestStepperCubit>().loadTests(),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('إعادة التحميل'),
            ),
          ),
        ],
      );
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'اختر الاختبار',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 12),
        ...state.tests.map((t) {
          final sel = state.selectedTest?['id'] == t['id'];
          return Card(
            margin: const EdgeInsets.only(bottom: 8),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
              side: BorderSide(
                color: sel ? const Color(0xFF2563EB) : Colors.transparent,
                width: 2,
              ),
            ),
            child: ListTile(
              title: Text(t['title']?.toString() ?? ''),
              subtitle: Text(
                [
                  if (t['scope_center_name'] != null)
                    t['scope_center_name'].toString(),
                  if (t['scheduled_at'] != null) t['scheduled_at'].toString(),
                ].join(' · '),
              ),
              onTap: () => context.read<ExamTestStepperCubit>().selectTest(t),
            ),
          );
        }),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: () {
            if (state.selectedTest == null) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('اختر اختباراً أولاً'),
                  backgroundColor: Color(0xFFDC2626),
                ),
              );
              return;
            }
            context.read<ExamTestStepperCubit>().goToStep2();
          },
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            backgroundColor: const Color(0xFF2563EB),
          ),
          child: const Text('التالي'),
        ),
      ],
    );
  }

  Widget _step2Students(BuildContext context, StepperDataState state) {
    if (state.loadingAssignments) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF2563EB)),
      );
    }
    final q = state.assignQuery.trim().toLowerCase();
    final filtered = state.assignments.where((row) {
      if (q.isEmpty) return true;
      final name = row['student_name']?.toString().toLowerCase() ?? '';
      return name.contains(q);
    }).toList();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        TextField(
          decoration: InputDecoration(
            hintText: 'بحث باسم الطالب',
            prefixIcon: const Icon(Icons.search_rounded),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          ),
          onChanged: (v) =>
              context.read<ExamTestStepperCubit>().setAssignQuery(v),
        ),
        const SizedBox(height: 16),
        ...filtered.map((row) {
          final done = row['has_result'] == true;
          return Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              title: Text(
                row['student_name']?.toString() ?? '',
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 15,
                ),
              ),
              subtitle: Text(
                '${row['halaqah_name'] ?? ''} · ${row['center_name'] ?? ''}',
                style: const TextStyle(fontSize: 12),
              ),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: done
                      ? const Color(0xFF059669).withValues(alpha: 0.15)
                      : Colors.orange.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  done ? 'مكتمل' : 'لم يُختبَر',
                  style: TextStyle(
                    color: done
                        ? const Color(0xFF059669)
                        : Colors.orange.shade800,
                    fontSize: 12,
                  ),
                ),
              ),
              onTap: () => _openStudentSheet(context, row),
            ),
          );
        }),
      ],
    );
  }

  Widget _step3Scores(BuildContext context, StepperDataState state) {
    final total = _computeTotal(
      state.memScore.round(),
      state.tajScore.round(),
      state.revScore.round(),
    );
    final level = levelLabelFromTotal(total);
    final col = levelAccentColor(total);

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        TextField(
          controller: _surahCtrl,
          decoration: InputDecoration(
            labelText: 'السورة المختبرة',
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          ),
          textAlign: TextAlign.right,
        ),
        const SizedBox(height: 20),
        Text(
          'الحفظ (50%)',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: Colors.blue.shade800,
          ),
        ),
        Slider(
          value: state.memScore,
          min: 0,
          max: 100,
          divisions: 100,
          activeColor: const Color(0xFF2563EB),
          label: '${state.memScore.round()}',
          onChanged: (v) =>
              context.read<ExamTestStepperCubit>().updateScores(mem: v),
        ),
        Text(
          'التجويد (30%)',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: Colors.green.shade800,
          ),
        ),
        Slider(
          value: state.tajScore,
          min: 0,
          max: 100,
          divisions: 100,
          activeColor: const Color(0xFF059669),
          label: '${state.tajScore.round()}',
          onChanged: (v) =>
              context.read<ExamTestStepperCubit>().updateScores(taj: v),
        ),
        Text(
          'المراجعة (20%)',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: Colors.orange.shade800,
          ),
        ),
        Slider(
          value: state.revScore,
          min: 0,
          max: 100,
          divisions: 100,
          activeColor: const Color(0xFFD97706),
          label: '${state.revScore.round()}',
          onChanged: (v) =>
              context.read<ExamTestStepperCubit>().updateScores(rev: v),
        ),
        const SizedBox(height: 12),
        Center(
          child: Column(
            children: [
              Text(
                '$total',
                style: const TextStyle(
                  fontSize: 36,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: col.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  level,
                  style: TextStyle(
                    color: col,
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _notesCtrl,
          maxLines: 4,
          maxLength: 500,
          decoration: InputDecoration(
            labelText: 'ملاحظات (اختياري)',
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          ),
          textAlign: TextAlign.right,
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: () {
            if (_surahCtrl.text.trim().isEmpty) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('أدخل السورة المختبرة'),
                  backgroundColor: Color(0xFFDC2626),
                ),
              );
              return;
            }
            context.read<ExamTestStepperCubit>().goToStep(3);
          },
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            backgroundColor: const Color(0xFF2563EB),
          ),
          child: const Text('المراجعة والحفظ'),
        ),
      ],
    );
  }

  Widget _step4Review(BuildContext context, StepperDataState state) {
    final a = state.selectedAssignment;
    if (a == null) return const SizedBox.shrink();

    final total = _computeTotal(
      state.memScore.round(),
      state.tajScore.round(),
      state.revScore.round(),
    );
    final level = levelLabelFromTotal(total);
    final col = levelAccentColor(total);

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  a['student_name']?.toString() ?? '',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
                Text(
                  '${a['halaqah_name'] ?? ''} · ${a['center_name'] ?? ''}',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        _reviewRow('الحفظ', '${state.memScore.round()}'),
        _reviewRow('التجويد', '${state.tajScore.round()}'),
        _reviewRow('المراجعة', '${state.revScore.round()}'),
        const Divider(height: 32),
        Center(
          child: Column(
            children: [
              Text(
                '$total',
                style: const TextStyle(
                  fontSize: 40,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: col.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  level,
                  style: TextStyle(color: col, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
        if (_notesCtrl.text.trim().isNotEmpty) ...[
          const SizedBox(height: 12),
          Text('ملاحظات: ${_notesCtrl.text}'),
        ],
        const SizedBox(height: 24),
        Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () =>
                    context.read<ExamTestStepperCubit>().goToStep(2),
                child: const Text('تعديل'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: FilledButton(
                onPressed: state.submitting
                    ? null
                    : () {
                        context.read<ExamTestStepperCubit>().submit(
                          surah: _surahCtrl.text.trim(),
                          notes: _notesCtrl.text.trim().isEmpty
                              ? null
                              : _notesCtrl.text.trim(),
                        );
                      },
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: state.submitting
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('حفظ النتيجة'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _reviewRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
