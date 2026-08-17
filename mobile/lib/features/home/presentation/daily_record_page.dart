import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_error_view.dart' show AppErrorView, AppLoadingView;
import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/teacher_daily_repository.dart';
import 'bloc/daily_record_cubit.dart'; // مسار الـ Cubit الجديد

/// شاشة "تسجيل اليوم" للمعلم (تم ترقيتها لتعمل بنظام Cubit).
class DailyRecordPage extends StatelessWidget {
  final TeacherDailyRepository? repository;

  const DailyRecordPage({super.key, this.repository});

  @override
  Widget build(BuildContext context) {
    // 1. توفير الـ Cubit للشجرة باستخدام BlocProvider
    return BlocProvider(
      create: (context) {
        final repo = repository ??
            TeacherDailyRepositoryImpl(
              apiClient: ApiClient(tokenStorage: SecureTokenStorage()),
            );
        final date = DateTime.now().toIso8601String().substring(0, 10);
        // إنشاء الـ Cubit والبدء بتحميل البيانات فوراً
        return DailyRecordCubit(repo)..loadData(date);
      },
      child: const _DailyRecordView(),
    );
  }
}

class _DailyRecordView extends StatefulWidget {
  const _DailyRecordView();

  @override
  State<_DailyRecordView> createState() => _DailyRecordViewState();
}

class _DailyRecordViewState extends State<_DailyRecordView> with WidgetsBindingObserver {
  final _searchCtrl = TextEditingController();
  
  String get _todayDate => DateTime.now().toIso8601String().substring(0, 10);
  
  // متغير للبحث المحلي
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    // تحديث النص عند الكتابة في مربع البحث
    _searchCtrl.addListener(() {
      setState(() {
        _searchQuery = _searchCtrl.text.trim().toLowerCase();
      });
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // إعادة التحميل عند العودة للتطبيق عبر استدعاء الكيوبت
      context.read<DailyRecordCubit>().loadData(_todayDate);
    }
  }

  /// فتح التفاصيل لكل طالب عبر BottomSheet.
  void _openDetails(StudentTodayDto student, List<EvaluationReasonDto> reasons) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _StudentDetailsSheet(
        student: student,
        reasons: reasons,
        // عند التعديل داخل الشيت، نحدث الواجهة عبر الكيوبت
        onChanged: () => context.read<DailyRecordCubit>().updateLocalState(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // 2. استخدام BlocConsumer للاستماع للأحداث الجانبية وبناء الواجهة
    return BlocConsumer<DailyRecordCubit, DailyRecordState>(
      listener: (context, state) {
        // الاستماع للأخطاء وإظهار SnackBar
        if (state is DailyRecordError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: Colors.red.shade800,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
        // الاستماع لنجاح الحفظ
        if (state is DailyRecordSavedSuccess) {
          final nav = Navigator.of(context);
          if (nav.canPop()) {
            nav.pop(true);
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: const Text('تم حفظ التسجيلات بنجاح'),
                behavior: SnackBarBehavior.floating,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            );
          }
        }
      },
      builder: (context, state) {
        // استخراج البيانات بناءً على الحالة الحالية
        bool isLoading = state is DailyRecordLoading;
        bool isSaving = state is DailyRecordSaving;
        List<StudentTodayDto> students = [];
        List<EvaluationReasonDto> reasons = [];

        if (state is DailyRecordLoaded) {
          students = state.students;
          reasons = state.reasons;
        } else if (state is DailyRecordSaving) {
          students = state.students;
          reasons = state.reasons;
        }

        // فلترة الطلاب بناءً على البحث
        final filteredStudents = _searchQuery.isEmpty 
            ? students 
            : students.where((s) => s.fullName.toLowerCase().contains(_searchQuery)).toList();

        return Scaffold(
          appBar: AppBar(
            title: const Text('تسجيل اليوم'),
            actions: [
              if (isSaving)
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 12),
                  child: Center(
                    child: SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                    ),
                  ),
                ),
            ],
          ),
          body: isLoading
              ? const AppLoadingView(message: 'جاري تحميل الطلاب...')
              : state is DailyRecordError && students.isEmpty
                  ? AppErrorView(message: state.message, onRetry: () => context.read<DailyRecordCubit>().loadData(_todayDate))
                  : Column(
                      children: [
                        _DateSummaryBar(date: _todayDate, count: filteredStudents.length),
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
                          child: TextField(
                            controller: _searchCtrl,
                            decoration: const InputDecoration(
                              hintText: 'بحث سريع عن طالب...',
                              prefixIcon: Icon(Icons.search_rounded),
                            ),
                          ),
                        ),
                        Expanded(
                          child: filteredStudents.isEmpty
                              ? Center(
                                  child: Text(
                                    students.isEmpty
                                        ? 'لا يوجد طلاب في هذه الحلقة لهذا اليوم'
                                        : 'لا يوجد طلاب يطابقون البحث',
                                    style: TextStyle(color: Colors.grey.shade600),
                                  ),
                                )
                              : ListView.builder(
                                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
                                  itemCount: filteredStudents.length,
                                  itemBuilder: (_, i) {
                                    final s = filteredStudents[i];
                                    return _StudentCard(
                                      student: s,
                                      onDetails: () => _openDetails(s, reasons),
                                      // عند الضغط على الحضور/التقييم نحدث الواجهة عبر الكيوبت
                                      onChanged: () => context.read<DailyRecordCubit>().updateLocalState(),
                                    );
                                  },
                                ),
                        ),
                      ],
                    ),
          floatingActionButton: FloatingActionButton.extended(
            key: const Key('save_all_button'),
            onPressed: isSaving 
                ? null 
                : () => context.read<DailyRecordCubit>().saveRecords(_todayDate, students, reasons),
            icon: isSaving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.save_rounded),
            label: Text(isSaving ? 'جاري الحفظ...' : 'حفظ الكل'),
            backgroundColor: AppColors.forest,
            foregroundColor: Colors.white,
          ),
        );
      },
    );
  }
}

// ----------------------------------------------------------------------
// الويدجت المستقلة (تبقى كما هي)
// ----------------------------------------------------------------------

class _DateSummaryBar extends StatelessWidget {
  final String date;
  final int count;

  const _DateSummaryBar({required this.date, required this.count});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.forest.withValues(alpha: 0.12),
            AppColors.mint.withValues(alpha: 0.08),
          ],
        ),
      ),
      child: Row(
        children: [
          const Icon(Icons.today_rounded, color: AppColors.forest),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'اليوم: $date',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: AppColors.forest.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              '$count طالب',
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: AppColors.forest,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// بطاقة الطالب ضمن شاشة تسجيل اليوم.
class _StudentCard extends StatelessWidget {
  final StudentTodayDto student;
  final VoidCallback onDetails;
  final VoidCallback onChanged;

  const _StudentCard({
    required this.student,
    required this.onDetails,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        elevation: 1,
        shadowColor: Colors.black12,
        borderRadius: BorderRadius.circular(16),
        color: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: AppColors.mint.withValues(alpha: 0.2),
                    foregroundColor: AppColors.forest,
                    child: Text(
                      student.fullName.isNotEmpty ? student.fullName.substring(0, 1) : '?',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      student.fullName,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                    ),
                  ),
                  TextButton.icon(
                    onPressed: onDetails,
                    icon: const Icon(Icons.tune_rounded, size: 18),
                    label: const Text('تفاصيل'),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                'الحضور',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 6),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  _Chip(
                    label: 'حاضر',
                    selected: student.attendanceStatus == 'present',
                    color: AppColors.mint,
                    onTap: () {
                      student.attendanceStatus = 'present';
                      onChanged();
                    },
                  ),
                  _Chip(
                    label: 'غياب مبرر',
                    selected: student.attendanceStatus == 'excused_absence',
                    color: Colors.orange,
                    onTap: () {
                      student.attendanceStatus = 'excused_absence';
                      onChanged();
                    },
                  ),
                  _Chip(
                    label: 'غياب غير مبرر',
                    selected: student.attendanceStatus == 'unexcused_absence',
                    color: Colors.red,
                    onTap: () {
                      student.attendanceStatus = 'unexcused_absence';
                      onChanged();
                    },
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                'التقييم',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600, fontWeight: FontWeight.w600),
              ),
              if (!student.canEditEvaluation) ...[
                const SizedBox(height: 4),
                Text(
                  'انتهت مهلة التعديل (ساعة واحدة من أول إرسال للتقييم)',
                  style: TextStyle(fontSize: 11, color: Colors.orange.shade800),
                ),
              ],
              const SizedBox(height: 6),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  _Chip(
                    label: 'متميز',
                    selected: student.evaluationOverall == 'excellent',
                    color: AppColors.forest,
                    enabled: student.canEditEvaluation,
                    onTap: () {
                      student.evaluationOverall = 'excellent';
                      onChanged();
                    },
                  ),
                  _Chip(
                    label: 'جيد',
                    selected: student.evaluationOverall == 'good',
                    color: Colors.blue,
                    enabled: student.canEditEvaluation,
                    onTap: () {
                      student.evaluationOverall = 'good';
                      onChanged();
                    },
                  ),
                  _Chip(
                    label: 'مقصر',
                    selected: student.evaluationOverall == 'needs_improvement',
                    color: Colors.deepOrange,
                    enabled: student.canEditEvaluation,
                    onTap: () {
                      student.evaluationOverall = 'needs_improvement';
                      onChanged();
                    },
                  ),
                  _Chip(
                    label: 'بدون',
                    selected: student.evaluationOverall == 'none' || student.evaluationOverall == null,
                    color: Colors.grey,
                    enabled: student.canEditEvaluation,
                    onTap: () {
                      student.evaluationOverall = 'none';
                      onChanged();
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Chip قابل للاختيار يُستخدم لتسجيل حالات الحضور والتقييم.
class _Chip extends StatelessWidget {
  final String label;
  final bool selected;
  final Color color;
  final bool enabled;
  final VoidCallback onTap;

  const _Chip({
    required this.label,
    required this.selected,
    required this.color,
    this.enabled = true,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: enabled ? 1 : 0.55,
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: enabled ? (_) => onTap() : null,
        selectedColor: color.withValues(alpha: 0.2),
        checkmarkColor: color,
        labelStyle: TextStyle(
          color: selected ? color : Colors.grey.shade800,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          fontSize: 12.5,
        ),
        side: BorderSide(color: selected ? color : Colors.grey.shade300),
      ),
    );
  }
}

/// BottomSheet لتفاصيل التقييم والحفظ لطالب واحد.
class _StudentDetailsSheet extends StatefulWidget {
  final StudentTodayDto student;
  final List<EvaluationReasonDto> reasons;
  final VoidCallback onChanged;

  const _StudentDetailsSheet({
    required this.student,
    required this.reasons,
    required this.onChanged,
  });

  @override
  State<_StudentDetailsSheet> createState() => _StudentDetailsSheetState();
}

class _StudentDetailsSheetState extends State<_StudentDetailsSheet> {
  final _searchCtrl = TextEditingController();
  late TextEditingController _generalNote;
  late TextEditingController _mistakes;
  late TextEditingController _memFrom;
  late TextEditingController _memTo;
  late TextEditingController _revFrom;
  late TextEditingController _revTo;
  String _q = '';

  @override
  void initState() {
    super.initState();
    final s = widget.student;
    _generalNote = TextEditingController(text: s.generalNote ?? '');
    _mistakes = TextEditingController(text: s.mistakes ?? '');
    _memFrom = TextEditingController(text: s.memorizationFrom ?? '');
    _memTo = TextEditingController(text: s.memorizationTo ?? '');
    _revFrom = TextEditingController(text: s.revisionFrom ?? '');
    _revTo = TextEditingController(text: s.revisionTo ?? '');
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _generalNote.dispose();
    _mistakes.dispose();
    _memFrom.dispose();
    _memTo.dispose();
    _revFrom.dispose();
    _revTo.dispose();
    super.dispose();
  }

  void _syncToStudent() {
    widget.student.generalNote = _generalNote.text.isEmpty ? null : _generalNote.text;
    widget.student.mistakes = _mistakes.text.isEmpty ? null : _mistakes.text;
    widget.student.memorizationFrom = _memFrom.text.isEmpty ? null : _memFrom.text;
    widget.student.memorizationTo = _memTo.text.isEmpty ? null : _memTo.text;
    widget.student.revisionFrom = _revFrom.text.isEmpty ? null : _revFrom.text;
    widget.student.revisionTo = _revTo.text.isEmpty ? null : _revTo.text;
    widget.onChanged();
  }

  @override
  Widget build(BuildContext context) {
    final reasons = widget.reasons.where((r) {
      final s = '${r.label} ${r.type}'.toLowerCase();
      return s.contains(_q.toLowerCase());
    }).toList();

    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.only(bottom: bottomInset),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              widget.student.fullName,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _searchCtrl,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.search_rounded),
                hintText: 'بحث في أسباب التميّز / التقصير',
              ),
              onChanged: (v) => setState(() => _q = v),
              enabled: widget.student.canEditEvaluation,
            ),
            const SizedBox(height: 12),
            Text('الأسباب', style: Theme.of(context).textTheme.titleSmall),
            if (!widget.student.canEditEvaluation) ...[
              const SizedBox(height: 4),
              Text(
                'لا يمكن تعديل أسباب التقييم أو الملاحظة العامة بعد مرور ساعة من أول إرسال.',
                style: TextStyle(fontSize: 12, color: Colors.orange.shade800),
              ),
            ],
            const SizedBox(height: 8),
            ...reasons.map((r) {
              final checked = widget.student.reasonIds.contains(r.id);
              return CheckboxListTile(
                value: checked,
                title: Text(r.label),
                subtitle: Text(r.type == 'excellence' ? 'تميّز' : 'تقصير'),
                onChanged: widget.student.canEditEvaluation
                    ? (v) {
                        setState(() {
                          if (v == true) {
                            widget.student.reasonIds.add(r.id);
                          } else {
                            widget.student.reasonIds.remove(r.id);
                          }
                          widget.onChanged();
                        });
                      }
                    : null,
              );
            }),
            const Divider(height: 28),
            TextField(
              controller: _generalNote,
              decoration: const InputDecoration(labelText: 'ملاحظة عامة'),
              maxLines: 2,
              readOnly: !widget.student.canEditEvaluation,
              onChanged: (_) => _syncToStudent(),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _mistakes,
              decoration: const InputDecoration(labelText: 'الأخطاء / الملاحظات على الحفظ'),
              maxLines: 3,
              onChanged: (_) => _syncToStudent(),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _memFrom,
                    decoration: const InputDecoration(labelText: 'الحفظ من'),
                    onChanged: (_) => _syncToStudent(),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _memTo,
                    decoration: const InputDecoration(labelText: 'الحفظ إلى'),
                    onChanged: (_) => _syncToStudent(),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _revFrom,
                    decoration: const InputDecoration(labelText: 'المراجعة من'),
                    onChanged: (_) => _syncToStudent(),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextField(
                    controller: _revTo,
                    decoration: const InputDecoration(labelText: 'المراجعة إلى'),
                    onChanged: (_) => _syncToStudent(),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}