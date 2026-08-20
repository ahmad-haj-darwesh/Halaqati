import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../data/supervisor_models.dart';
import '../data/supervisor_repository.dart';
import 'supervisory_visit_form_page.dart';
import '../presentation/bloc/my_visits_cubit.dart'; // مسار الكيوبت

const _g0 = Color(0xFF2563EB);

class MyVisitsPage extends StatelessWidget {
  const MyVisitsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final repo = sl<SupervisorRepository>();
        return MyVisitsCubit(repo)..loadInitial();
      },
      child: const _MyVisitsView(),
    );
  }
}

/// نستخدم StatefulWidget هنا فقط لإدارة ScrollController (واجهة مستخدم)
class _MyVisitsView extends StatefulWidget {
  const _MyVisitsView();

  @override
  State<_MyVisitsView> createState() => _MyVisitsViewState();
}

class _MyVisitsViewState extends State<_MyVisitsView> {
  final ScrollController _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!mounted) return;
    // التحقق مما إذا كان المستخدم قد اقترب من نهاية القائمة
    final pos = _scroll.position;
    if (pos.pixels > pos.maxScrollExtent - 200) {
      context.read<MyVisitsCubit>().loadMore();
    }
  }

  Color _avgColor(double a) {
    if (a >= 8) return const Color(0xFF059669);
    if (a >= 6) return _g0;
    if (a >= 4) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('سجل زياراتي')),
      body: RefreshIndicator(
        color: _g0,
        onRefresh: () => context.read<MyVisitsCubit>().loadInitial(),
        child: BlocBuilder<MyVisitsCubit, MyVisitsState>(
          builder: (context, state) {
            if (state is MyVisitsLoading) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator(color: _g0)),
                ],
              );
            }

            if (state is MyVisitsError) {
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
                      onPressed: () => context.read<MyVisitsCubit>().loadInitial(),
                      child: const Text('إعادة المحاولة'),
                    ),
                  ),
                ],
              );
            }

            if (state is MyVisitsLoaded) {
              if (state.items.isEmpty) {
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: [
                    const SizedBox(height: 80),
                    Icon(Icons.history_rounded, size: 72, color: Colors.grey.shade400),
                    const SizedBox(height: 16),
                    Text(
                      'لم تسجّل أي زيارات بعد',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Colors.grey.shade800),
                    ),
                    const SizedBox(height: 24),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 24),
                      child: FilledButton(
                        style: FilledButton.styleFrom(backgroundColor: _g0, minimumSize: const Size(double.infinity, 48)),
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute<void>(builder: (_) => const SupervisoryVisitFormPage()),
                          );
                        },
                        child: const Text('تسجيل أول زيارة'),
                      ),
                    ),
                  ],
                );
              }

              return ListView.builder(
                controller: _scroll,
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                itemCount: state.items.length + (state.isFetchingMore ? 1 : 0),
                itemBuilder: (_, i) {
                  // إذا وصلنا للنهاية ونحن نقوم بجلب بيانات، نعرض مؤشر تحميل أسفل القائمة
                  if (i >= state.items.length) {
                    return const Padding(
                      padding: EdgeInsets.all(16),
                      child: Center(child: CircularProgressIndicator(color: _g0)),
                    );
                  }
                  final v = state.items[i];
                  return _VisitCard(v: v, avgColor: _avgColor(v.avgScore));
                },
              );
            }

            return const SizedBox();
          },
        ),
      ),
    );
  }
}

class _VisitCard extends StatelessWidget {
  const _VisitCard({required this.v, required this.avgColor});

  final VisitItem v;
  final Color avgColor;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        title: Row(
          children: [
            Expanded(child: Text(v.visitDate, style: const TextStyle(fontWeight: FontWeight.w700))),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: avgColor.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                v.avgScore.toStringAsFixed(1),
                style: TextStyle(color: avgColor, fontWeight: FontWeight.w800),
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 6),
            Text('المعلّم: ${v.teacherName}'),
            Text('المركز: ${v.centerName}'),
            const Divider(height: 16),
            Text('مهارة: ${v.teachingScore}  ·  خطة: ${v.planScore}  ·  تفاعل: ${v.engagementScore}'),
          ],
        ),
        children: [
          if (v.notes != null && v.notes!.isNotEmpty)
            Align(
              alignment: Alignment.centerRight,
              child: Text('ملاحظات:\n${v.notes}', style: const TextStyle(height: 1.4)),
            ),
          if (v.recommendations != null && v.recommendations!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Align(
                alignment: Alignment.centerRight,
                child: Text('توصيات:\n${v.recommendations}', style: const TextStyle(height: 1.4)),
              ),
            ),
        ],
      ),
    );
  }
}