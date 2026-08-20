import 'package:fl_chart/fl_chart.dart';
import '../../../core/utils/arabic_plural.dart';
import 'package:flutter/material.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/errors/api_exception.dart';
import '../data/examiner_models.dart';
import '../data/examiner_repository.dart';

/// BottomSheet لعرض تفاصيل طالب قبل تأكيد اختباره.
///
/// Arabic: يعرض معلومات الطالب، آخر النتائج، ومخطط تطور الدرجات (إن توفر)، ثم يتيح
/// تأكيد اختيار الطالب للانتقال لخطوة إدخال الدرجات.
/// EN: Student detail bottom sheet used before confirming an assignment selection.
Future<void> showExaminerStudentDetailSheet({
  required BuildContext context,
  required ExaminerRepository repo,
  required int studentId,
  required VoidCallback onConfirm,
}) async {
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => _StudentDetailSheetContent(
      repo: repo,
      studentId: studentId,
      onConfirm: () {
        Navigator.pop(ctx);
        onConfirm();
      },
    ),
  );
}

class _StudentDetailSheetContent extends StatefulWidget {
  final ExaminerRepository repo;
  final int studentId;
  final VoidCallback onConfirm;

  const _StudentDetailSheetContent({
    required this.repo,
    required this.studentId,
    required this.onConfirm,
  });

  @override
  State<_StudentDetailSheetContent> createState() => _StudentDetailSheetContentState();
}

class _StudentDetailSheetContentState extends State<_StudentDetailSheetContent> {
  StudentDetail? _detail;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final d = await widget.repo.fetchStudentDetail(widget.studentId);
      if (!mounted) return;
      setState(() {
        _detail = d;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = friendlyErrorMessage(e);
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.sizeOf(context).height * 0.6;
    return Container(
      height: h,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 10),
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(4)),
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
                : _error != null
                    ? Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(_error!, textAlign: TextAlign.center)))
                    : _buildBody(),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    final d = _detail!;
    final photo = d.photoUrl != null && d.photoUrl!.isNotEmpty
        ? ApiConstants.resolvePublicMediaUrl(d.photoUrl)
        : '';

    final chartScores = d.lastResults.map((e) => e.totalScore.toDouble()).toList();
    final lastThree = d.lastResults.take(3).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 28,
              backgroundColor: Colors.grey.shade200,
              backgroundImage: photo.isNotEmpty ? NetworkImage(photo) : null,
              child: photo.isEmpty ? const Icon(Icons.person_rounded, size: 32) : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(d.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text(
                    '${d.halaqahName ?? '—'} • ${d.centerName ?? '—'}',
                    style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        const Divider(),
        const SizedBox(height: 8),
        Text('معلومات الحفظ', style: TextStyle(fontWeight: FontWeight.w700, color: Colors.grey.shade800)),
        const SizedBox(height: 8),
        Text('الأجزاء المحفوظة: ${ArCount.parts(d.memorizedParts)}', style: const TextStyle(fontSize: 14)),
        const SizedBox(height: 4),
        Text('السورة الحالية: ${d.currentSurah ?? '—'}', style: const TextStyle(fontSize: 14)),
        const SizedBox(height: 16),
        Text('آخر الاختبارات', style: TextStyle(fontWeight: FontWeight.w700, color: Colors.grey.shade800)),
        const SizedBox(height: 8),
        if (lastThree.isEmpty)
          Text('لا توجد اختبارات سابقة', style: TextStyle(color: Colors.grey.shade600))
        else
          ...lastThree.map(
            (r) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Expanded(child: Text(r.date, style: const TextStyle(fontSize: 13))),
                  Expanded(child: Text(r.testedSurah ?? '—', style: const TextStyle(fontSize: 13))),
                  Text('${r.totalScore}', style: const TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: levelAccentColor(r.totalScore).withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      r.levelAr ?? levelLabelFromTotal(r.totalScore),
                      style: TextStyle(fontSize: 12, color: levelAccentColor(r.totalScore)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        const SizedBox(height: 12),
        if (chartScores.length >= 2) ...[
          Text('تطور الدرجات', style: TextStyle(fontWeight: FontWeight.w700, color: Colors.grey.shade800)),
          const SizedBox(height: 8),
          SizedBox(
            height: 140,
            child: LineChart(
              LineChartData(
                gridData: const FlGridData(show: false),
                titlesData: const FlTitlesData(show: false),
                borderData: FlBorderData(show: false),
                lineBarsData: [
                  LineChartBarData(
                    spots: [
                      for (var i = 0; i < chartScores.length; i++)
                        FlSpot(i.toDouble(), chartScores[i]),
                    ],
                    isCurved: true,
                    color: const Color(0xFF2563EB),
                    barWidth: 3,
                    dotData: FlDotData(show: true),
                  ),
                ],
                minY: 0,
                maxY: 100,
              ),
            ),
          ),
        ],
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: FilledButton(
            onPressed: widget.onConfirm,
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            child: const Text('تأكيد اختيار الطالب'),
          ),
        ),
      ],
    );
  }
}
