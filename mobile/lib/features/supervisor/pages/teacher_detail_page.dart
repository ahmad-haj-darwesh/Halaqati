import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/supervisor_models.dart';
import '../data/supervisor_repository.dart';
import 'supervisory_visit_form_page.dart';
import '../presentation/bloc/teacher_detail_cubit.dart'; // مسار الكيوبت

const _g0 = Color(0xFF2563EB);

/// نقطة الإدخال وحقن الكيوبت (Stateless)
class TeacherDetailPage extends StatelessWidget {
  const TeacherDetailPage({
    super.key,
    required this.teacherId,
    required this.teacherName,
  });

  final int teacherId;
  final String teacherName;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        final repo = SupervisorRepositoryImpl(apiClient: apiClient);
        return TeacherDetailCubit(repo)..loadDetail(teacherId);
      },
      child: _TeacherDetailView(teacherId: teacherId, teacherName: teacherName),
    );
  }
}

/// واجهة المستخدم التي تدير الأنيميشن (Stateful)
class _TeacherDetailView extends StatefulWidget {
  final int teacherId;
  final String teacherName;

  const _TeacherDetailView({required this.teacherId, required this.teacherName});

  @override
  State<_TeacherDetailView> createState() => _TeacherDetailViewState();
}

class _TeacherDetailViewState extends State<_TeacherDetailView> with SingleTickerProviderStateMixin {
  late final AnimationController _barCtrl;

  @override
  void initState() {
    super.initState();
    _barCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 900));
  }

  @override
  void dispose() {
    _barCtrl.dispose();
    super.dispose();
  }

  Color _scoreColor(int s) {
    if (s >= 8) return const Color(0xFF059669);
    if (s >= 6) return _g0;
    if (s >= 4) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.teacherName)),
      body: RefreshIndicator(
        color: _g0,
        onRefresh: () => context.read<TeacherDetailCubit>().loadDetail(widget.teacherId),
        child: BlocConsumer<TeacherDetailCubit, TeacherDetailState>(
          listener: (context, state) {
            if (state is TeacherDetailLoaded) {
              _barCtrl.forward(from: 0);
            }
          },
          builder: (context, state) {
            return _buildBody(context, state);
          },
        ),
      ),
    );
  }

  Widget _buildBody(BuildContext context, TeacherDetailState state) {
    if (state is TeacherDetailLoading) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator(color: _g0)),
        ],
      );
    }

    if (state is TeacherDetailError) {
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
              onPressed: () => context.read<TeacherDetailCubit>().loadDetail(widget.teacherId),
              child: const Text('إعادة المحاولة'),
            ),
          ),
        ],
      );
    }

    if (state is TeacherDetailLoaded) {
      final d = state.detail;
      final photo = ApiConstants.resolvePublicMediaUrl(d.photoUrl);
      final subtitle = d.halaqahs.isNotEmpty
          ? '${d.halaqahs.first['name'] ?? ''} · ${d.halaqahs.first['center_name'] ?? ''}'
          : '';

      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: CircleAvatar(
              radius: 36,
              backgroundColor: Colors.grey.shade200,
              backgroundImage: photo.isNotEmpty ? NetworkImage(photo) : null,
              child: photo.isEmpty ? const Icon(Icons.person_rounded, size: 40) : null,
            ),
          ),
          const SizedBox(height: 12),
          Center(
            child: Text(d.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ),
          if (subtitle.isNotEmpty)
            Center(
              child: Text(subtitle, style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
            ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _kpi('${d.studentCount}', 'طلاب')),
              const SizedBox(width: 8),
              Expanded(child: _kpi('${d.visitsCount}', 'زيارة')),
              const SizedBox(width: 8),
              Expanded(
                child: _kpi(
                  d.overallAvg != null ? d.overallAvg!.toStringAsFixed(1) : '—',
                  'متوسط',
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            d.lastVisitDate != null ? 'آخر زيارة: ${d.lastVisitDate}' : 'لم يُزار',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: d.lastVisitDate != null ? Colors.grey.shade800 : Colors.orange.shade800,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            style: FilledButton.styleFrom(
              minimumSize: const Size(double.infinity, 48),
              backgroundColor: _g0,
            ),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute<void>(
                  builder: (_) => SupervisoryVisitFormPage(presetTeacherUserId: widget.teacherId),
                ),
              );
            },
            child: const Text('زيارة جديدة'),
          ),
          const SizedBox(height: 24),
          const Center(child: Text('— — — تاريخ الزيارات — — —', style: TextStyle(color: Colors.grey))),
          const SizedBox(height: 12),
          if (d.lastVisits.isEmpty)
            Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'لا توجد زيارات مسجّلة بعد',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey.shade700),
              ),
            )
          else
            ...d.lastVisits.map(_visitCard),
        ],
      );
    }

    return const SizedBox();
  }

  Widget _kpi(String v, String l) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Text(v, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
          Text(l, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
        ],
      ),
    );
  }

  Widget _visitCard(PastVisit v) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(v.date, style: const TextStyle(fontWeight: FontWeight.w700)),
                const Spacer(),
                Text('متوسط ${v.avgScore.toStringAsFixed(1)}',
                    style: TextStyle(color: _scoreColor(v.avgScore.round()), fontWeight: FontWeight.w700)),
              ],
            ),
            const SizedBox(height: 10),
            _animatedBar('مهارة الإعطاء', v.teachingScore),
            _animatedBar('الالتزام بالخطة', v.planScore),
            _animatedBar('تفاعل الطلاب', v.engagementScore),
            if (v.notes != null && v.notes!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(v.notes!, style: TextStyle(fontSize: 13, color: Colors.grey.shade800)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _animatedBar(String label, int score) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(label, style: const TextStyle(fontSize: 12))),
              Text('$score', style: TextStyle(fontWeight: FontWeight.w700, color: _scoreColor(score))),
            ],
          ),
          const SizedBox(height: 4),
          AnimatedBuilder(
            animation: _barCtrl,
            builder: (_, __) {
              final t = Curves.easeOutCubic.transform(_barCtrl.value);
              return ClipRRect(
                borderRadius: BorderRadius.circular(3),
                child: LinearProgressIndicator(
                  value: t * (score / 10),
                  minHeight: 6,
                  backgroundColor: Colors.grey.shade200,
                  color: _scoreColor(score),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}