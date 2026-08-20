import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import '../data/supervisor_repository.dart';
import '../presentation/bloc/supervisory_visit_form_cubit.dart'; // مسار الكيوبت

const _g0 = Color(0xFF2563EB);
const _g1 = Color(0xFF1D4ED8);

class SupervisoryVisitFormPage extends StatelessWidget {
  const SupervisoryVisitFormPage({super.key, this.presetTeacherUserId});

  final int? presetTeacherUserId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final repo = sl<SupervisorRepository>();
        return SupervisoryVisitFormCubit(repo)
          ..loadMeta(presetTeacherUserId: presetTeacherUserId);
      },
      child: const _SupervisoryVisitFormView(),
    );
  }
}

class _SupervisoryVisitFormView extends StatefulWidget {
  const _SupervisoryVisitFormView();

  @override
  State<_SupervisoryVisitFormView> createState() =>
      _SupervisoryVisitFormViewState();
}

class _SupervisoryVisitFormViewState extends State<_SupervisoryVisitFormView> {
  final PageController _page = PageController();
  final _notesCtrl = TextEditingController();
  final _recCtrl = TextEditingController();

  @override
  void dispose() {
    _page.dispose();
    _notesCtrl.dispose();
    _recCtrl.dispose();
    super.dispose();
  }

  String _levelAr(double a) {
    if (a >= 8) return 'ممتاز';
    if (a >= 6) return 'جيد';
    if (a >= 4) return 'متوسط';
    return 'يحتاج تحسيناً';
  }

  Color _levelColor(double a) {
    if (a >= 8) return const Color(0xFF059669);
    if (a >= 6) return _g0;
    if (a >= 4) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  String _centerLabel(SupervisoryVisitFormState state) {
    if (state.centerId == null) return 'اختر';
    for (final c in state.centers) {
      if (c['id'].toString() == state.centerId.toString()) {
        return c['name']?.toString() ?? 'اختر';
      }
    }
    return 'اختر';
  }

  String _teacherLabel(SupervisoryVisitFormState state) {
    if (state.teacherUserId == null) return 'اختر';
    for (final t in state.teachersForCenter) {
      if (t['user_id'].toString() == state.teacherUserId.toString()) {
        final n = t['teacher_name']?.toString() ?? '';
        final h = t['halaqah_name']?.toString() ?? '';
        return h.isNotEmpty ? '$n — $h' : n;
      }
    }
    return 'اختر';
  }

  Future<void> _openCenterPicker(
    BuildContext context,
    SupervisoryVisitFormState state,
  ) async {
    if (state.centers.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('لا توجد مراكز ضمن صلاحيتك')),
      );
      return;
    }
    final picked = await showModalBottomSheet<int>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) {
        final maxH = MediaQuery.sizeOf(context).height * 0.55;
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Padding(
                padding: EdgeInsets.all(12),
                child: Text(
                  'اختر المركز',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                ),
              ),
              SizedBox(
                height: maxH,
                child: ListView(
                  children: state.centers.map((c) {
                    final id = int.tryParse(c['id'].toString());
                    return ListTile(
                      title: Text(c['name']?.toString() ?? ''),
                      onTap: () => Navigator.pop(ctx, id),
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
        );
      },
    );
    if (picked != null && context.mounted) {
      context.read<SupervisoryVisitFormCubit>().selectCenter(picked);
    }
  }

  Future<void> _openTeacherPicker(
    BuildContext context,
    SupervisoryVisitFormState state,
  ) async {
    final list = state.teachersForCenter;
    if (state.centerId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('اختر المركز أولاً')));
      return;
    }
    if (list.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('لا يوجد معلّمون في هذا المركز')),
      );
      return;
    }
    final picked = await showModalBottomSheet<int>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) {
        final maxH = MediaQuery.sizeOf(context).height * 0.55;
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Padding(
                padding: EdgeInsets.all(12),
                child: Text(
                  'اختر المعلّم',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                ),
              ),
              SizedBox(
                height: maxH,
                child: ListView(
                  children: list.map((t) {
                    final uid = int.tryParse(t['user_id'].toString());
                    final n = t['teacher_name']?.toString() ?? '';
                    final h = t['halaqah_name']?.toString() ?? '';
                    return ListTile(
                      title: Text(n),
                      subtitle: h.isNotEmpty ? Text(h) : null,
                      onTap: () => Navigator.pop(ctx, uid),
                    );
                  }).toList(),
                ),
              ),
            ],
          ),
        );
      },
    );
    if (picked != null && context.mounted) {
      context.read<SupervisoryVisitFormCubit>().selectTeacher(picked);
    }
  }

  Widget _pickerTile({
    required String label,
    required String valueText,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: InputDecorator(
          decoration: InputDecoration(
            labelText: label,
            border: const OutlineInputBorder(),
            suffixIcon: const Icon(Icons.arrow_drop_down_rounded),
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Align(
              alignment: Alignment.centerRight,
              child: Text(
                valueText,
                style: TextStyle(
                  fontSize: 16,
                  color: valueText == 'اختر'
                      ? Colors.grey.shade600
                      : Colors.black87,
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    SupervisoryVisitFormState state,
  ) async {
    final d = await showDatePicker(
      context: context,
      initialDate: state.visitDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (d != null && context.mounted) {
      final today = DateTime.now();
      final vd = DateTime(d.year, d.month, d.day);
      final tn = DateTime(today.year, today.month, today.day);
      if (vd.isAfter(tn)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('لا يمكن اختيار تاريخ في المستقبل')),
        );
        return;
      }
      context.read<SupervisoryVisitFormCubit>().setVisitDate(d);
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<SupervisoryVisitFormCubit, SupervisoryVisitFormState>(
      listenWhen: (previous, current) {
        return previous.step != current.step ||
            previous.submitError != current.submitError;
      },
      listener: (context, state) {
        if (state.submitError != null) {
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(SnackBar(content: Text(state.submitError!)));
        }
        if (_page.hasClients && _page.page?.round() != state.step) {
          _page.animateToPage(
            state.step,
            duration: const Duration(milliseconds: 280),
            curve: Curves.easeOutCubic,
          );
        }
      },
      builder: (context, state) {
        if (state.successResponse != null) {
          final avg = state.successResponse!.avgScore;
          return Scaffold(
            appBar: AppBar(title: const Text('تم الحفظ')),
            body: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  const Spacer(),
                  TweenAnimationBuilder<double>(
                    tween: Tween(begin: 0, end: 1),
                    duration: const Duration(milliseconds: 500),
                    builder: (_, v, child) =>
                        Transform.scale(scale: v, child: child),
                    child: Icon(
                      Icons.check_circle_rounded,
                      size: 88,
                      color: Colors.green.shade600,
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Text(
                    'تم تسجيل الزيارة بنجاح',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'المتوسط: ${avg.toStringAsFixed(1)} — ${_levelAr(avg)}',
                    style: TextStyle(
                      fontSize: 16,
                      color: _levelColor(avg),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const Spacer(),
                  FilledButton(
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(double.infinity, 48),
                      backgroundColor: _g0,
                    ),
                    onPressed: () {
                      _notesCtrl.clear();
                      _recCtrl.clear();
                      context.read<SupervisoryVisitFormCubit>().resetForm();
                      _page.jumpToPage(0);
                    },
                    child: const Text('تسجيل زيارة جديدة'),
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(double.infinity, 48),
                    ),
                    onPressed: () => Navigator.pop(context),
                    child: const Text('العودة للرئيسية'),
                  ),
                ],
              ),
            ),
          );
        }

        return Scaffold(
          appBar: AppBar(
            title: const Text('تسجيل زيارة إشرافية'),
            leading: IconButton(
              icon: Icon(
                state.step == 0
                    ? Icons.close_rounded
                    : Icons.arrow_back_rounded,
              ),
              onPressed: () {
                if (state.step == 0) {
                  Navigator.pop(context);
                } else {
                  context.read<SupervisoryVisitFormCubit>().setStep(
                    state.step - 1,
                  );
                }
              },
            ),
          ),
          body: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(3, (i) {
                    final active = i == state.step;
                    final done = i < state.step;
                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: done || active ? _g0 : Colors.grey.shade300,
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
                    _step1(context, state),
                    _step2(context, state),
                    _step3(context, state),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _step1(BuildContext context, SupervisoryVisitFormState state) {
    if (state.loadingMeta)
      return const Center(child: CircularProgressIndicator(color: _g0));

    if (state.metaError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(state.metaError!, textAlign: TextAlign.center),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () =>
                    context.read<SupervisoryVisitFormCubit>().loadMeta(),
                child: const Text('إعادة المحاولة'),
              ),
            ],
          ),
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'اختر المركز والمعلّم وتاريخ الزيارة',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
        if (state.centers.isEmpty) ...[
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Text(
              'لا توجد مراكز مرتبطة بحسابك. تأكد في لوحة الإدارة أنّك مسؤول عن مركز (حقل مشرف المركز).',
              style: TextStyle(color: Colors.orange.shade900, height: 1.35),
            ),
          ),
        ],
        const SizedBox(height: 12),
        _pickerTile(
          label: 'المركز',
          valueText: _centerLabel(state),
          onTap: () => _openCenterPicker(context, state),
        ),
        const SizedBox(height: 16),
        _pickerTile(
          label: 'المعلّم',
          valueText: _teacherLabel(state),
          onTap: () => _openTeacherPicker(context, state),
        ),
        const SizedBox(height: 16),
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('تاريخ الزيارة'),
          subtitle: Text(DateFormat('dd/MM/yyyy').format(state.visitDate)),
          trailing: const Icon(Icons.calendar_today_rounded),
          onTap: () => _pickDate(context, state),
        ),
        const SizedBox(height: 24),
        FilledButton(
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            backgroundColor: _g0,
          ),
          onPressed: () {
            if (state.centerId == null || state.teacherUserId == null) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('اختر المركز والمعلّم')),
              );
              return;
            }
            context.read<SupervisoryVisitFormCubit>().setStep(1);
          },
          child: const Text('التالي'),
        ),
      ],
    );
  }

  Widget _scoreBlock(
    BuildContext context,
    String title,
    String desc,
    int value,
    ValueChanged<int> onChanged,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
        ),
        const SizedBox(height: 4),
        Text(
          desc,
          style: TextStyle(
            fontSize: 13,
            color: Colors.grey.shade700,
            height: 1.35,
          ),
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 6,
          runSpacing: 6,
          children: List.generate(10, (i) {
            final s = i + 1;
            final sel = value == s;
            return SizedBox(
              width: 36,
              height: 36,
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(8),
                child: InkWell(
                  borderRadius: BorderRadius.circular(8),
                  onTap: () => onChanged(s),
                  child: Container(
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: sel ? _g0 : Colors.grey.shade400,
                      ),
                      gradient: sel
                          ? const LinearGradient(
                              colors: [_g0, _g1],
                              begin: Alignment.topRight,
                              end: Alignment.bottomLeft,
                            )
                          : null,
                    ),
                    child: Text(
                      '$s',
                      style: TextStyle(
                        color: sel ? Colors.white : Colors.grey.shade800,
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
              ),
            );
          }),
        ),
        const SizedBox(height: 20),
      ],
    );
  }

  Widget _step2(BuildContext context, SupervisoryVisitFormState state) {
    final a = state.avgScore;
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _scoreBlock(
          context,       
          'مهارة الإعطاء',
          'هل المعلّم يشرح بوضوح ويتابع مستوى الطلاب؟',
          state.teachScore,
          (v) =>
              context.read<SupervisoryVisitFormCubit>().updateScores(teach: v),
        ),
        _scoreBlock(
          context,
          'الالتزام بالخطة',
          'هل المعلّم يسير وفق الخطة المحددة من الإدارة؟',
          state.planScore,
          (v) =>
              context.read<SupervisoryVisitFormCubit>().updateScores(plan: v),
        ),
        _scoreBlock(
          context,
          'تفاعل الطلاب',
          'هل الطلاب منتبهون ومتفاعلون أثناء الحلقة؟',
          state.engageScore,
          (v) =>
              context.read<SupervisoryVisitFormCubit>().updateScores(engage: v),
        ),
        Center(
          child: Column(
            children: [
              Text(
                a.toStringAsFixed(1),
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _levelAr(a),
                style: TextStyle(
                  color: _levelColor(a),
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        FilledButton(
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 48),
            backgroundColor: _g0,
          ),
          onPressed: () => context.read<SupervisoryVisitFormCubit>().setStep(2),
          child: const Text('التالي'),
        ),
      ],
    );
  }

  Widget _step3(BuildContext context, SupervisoryVisitFormState state) {
    final teacherName = _teacherLabel(state).split(' — ').first;
    final centerName = _centerLabel(state);
    final avg = state.avgScore;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        TextField(
          controller: _notesCtrl,
          maxLines: 4,
          maxLength: 1000,
          decoration: const InputDecoration(
            labelText: 'ملاحظات',
            hintText: 'اكتب ملاحظاتك حول الزيارة...',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _recCtrl,
          maxLines: 4,
          maxLength: 1000,
          decoration: const InputDecoration(
            labelText: 'التوصيات',
            hintText: 'اكتب توصياتك للمعلّم...',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 16),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$teacherName — $centerName',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(DateFormat('dd/MM/yyyy').format(state.visitDate)),
                const Divider(height: 20),
                _miniScore('مهارة', state.teachScore),
                _miniScore('خطة', state.planScore),
                _miniScore('تفاعل', state.engageScore),
                const SizedBox(height: 8),
                Text(
                  'المتوسط: ${avg.toStringAsFixed(1)} (${_levelAr(avg)})',
                  style: TextStyle(
                    color: _levelColor(avg),
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 20),
        FilledButton(
          style: FilledButton.styleFrom(
            minimumSize: const Size(double.infinity, 50),
            backgroundColor: _g0,
          ),
          onPressed: state.submitting
              ? null
              : () => context.read<SupervisoryVisitFormCubit>().submit(
                  notes: _notesCtrl.text,
                  recommendations: _recCtrl.text,
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
              : const Text('حفظ الزيارة'),
        ),
      ],
    );
  }

  Widget _miniScore(String l, int s) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          SizedBox(width: 56, child: Text(l)),
          Expanded(
            child: LinearProgressIndicator(
              value: s / 10,
              borderRadius: BorderRadius.circular(3),
              color: _levelColor(s.toDouble()),
              backgroundColor: Colors.grey.shade200,
              minHeight: 6,
            ),
          ),
          const SizedBox(width: 8),
          Text('$s'),
        ],
      ),
    );
  }
}
