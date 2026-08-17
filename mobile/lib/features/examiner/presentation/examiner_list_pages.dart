import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../data/examiner_repository.dart';
import 'bloc/examiner_list_cubits.dart'; // مسار الـ Cubits الجديد

// ==========================================
// واجهة Scaffold مشتركة (Shared UI) لتجنب تكرار الكود
// ==========================================
class _SharedListScaffold extends StatelessWidget {
  final String title;
  final bool isLoading;
  final String? error;
  final VoidCallback onRefresh;
  final ValueChanged<String> onSearch;
  final Widget? belowSearch;
  final List<Map<String, dynamic>> items;
  final Widget Function(Map<String, dynamic> row) itemBuilder;

  const _SharedListScaffold({
    required this.title,
    required this.isLoading,
    this.error,
    required this.onRefresh,
    required this.onSearch,
    this.belowSearch,
    required this.items,
    required this.itemBuilder,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
          : error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(error!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        FilledButton(onPressed: onRefresh, child: const Text('إعادة المحاولة')),
                      ],
                    ),
                  ),
                )
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                      child: TextField(
                        onChanged: onSearch,
                        decoration: InputDecoration(
                          hintText: 'بحث…',
                          prefixIcon: const Icon(Icons.search_rounded),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                          isDense: true,
                        ),
                      ),
                    ),
                    if (belowSearch != null) belowSearch!,
                    Expanded(
                      child: RefreshIndicator(
                        color: const Color(0xFF2563EB),
                        onRefresh: () async => onRefresh(),
                        child: items.isEmpty
                            ? ListView(
                                children: const [
                                  SizedBox(height: 120),
                                  Center(child: Text('لا توجد بيانات تطابق البحث')),
                                ],
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                                itemCount: items.length,
                                separatorBuilder: (_, __) => const SizedBox(height: 8),
                                itemBuilder: (_, i) => itemBuilder(items[i]),
                              ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

// ==========================================
// 1. قائمة الاختبارات المتاحة
// ==========================================
class ExaminerTestsListPage extends StatelessWidget {
  const ExaminerTestsListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExaminerTestsCubit(
        ExaminerRepositoryImpl(apiClient: ApiClient(tokenStorage: SecureTokenStorage())),
      )..load(),
      child: BlocBuilder<ExaminerTestsCubit, ExaminerTestsState>(
        builder: (context, state) {
          final isLoading = state is TestsLoading;
          final error = state is TestsError ? state.message : null;
          final items = state is TestsLoaded ? state.filtered : <Map<String, dynamic>>[];

          return _SharedListScaffold(
            title: 'الاختبارات',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<ExaminerTestsCubit>().load(),
            onSearch: (q) => context.read<ExaminerTestsCubit>().search(q),
            items: items,
            itemBuilder: (row) => Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              child: ListTile(
                title: Text(row['title']?.toString() ?? ''),
                subtitle: Text(
                  [
                    if (row['type'] != null) row['type'].toString(),
                    if (row['scope_center_name'] != null) row['scope_center_name'].toString(),
                  ].join(' · '),
                ),
                isThreeLine: true,
              ),
            ),
          );
        },
      ),
    );
  }
}

// ==========================================
// 2. قائمة التعيينات
// ==========================================
class ExaminerAssignmentsListPage extends StatelessWidget {
  const ExaminerAssignmentsListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExaminerAssignmentsCubit(
        ExaminerRepositoryImpl(apiClient: ApiClient(tokenStorage: SecureTokenStorage())),
      )..load(),
      child: BlocBuilder<ExaminerAssignmentsCubit, ExaminerAssignmentsState>(
        builder: (context, state) {
          final isLoading = state is AssignmentsLoading;
          final error = state is AssignmentsError ? state.message : null;
          final items = state is AssignmentsLoaded ? state.filtered : <Map<String, dynamic>>[];
          final currentChip = state is AssignmentsLoaded ? state.chipIndex : 0;

          return _SharedListScaffold(
            title: 'تعيينات الاختبارات',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<ExaminerAssignmentsCubit>().load(),
            onSearch: (q) => context.read<ExaminerAssignmentsCubit>().filter(query: q),
            belowSearch: Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
              child: Wrap(
                spacing: 8,
                children: [
                  FilterChip(
                    label: const Text('الكل'),
                    selected: currentChip == 0,
                    onSelected: (_) => context.read<ExaminerAssignmentsCubit>().filter(chip: 0),
                    selectedColor: const Color(0xFF2563EB).withValues(alpha: 0.2),
                  ),
                  FilterChip(
                    label: const Text('لم يُختبَر'),
                    selected: currentChip == 1,
                    onSelected: (_) => context.read<ExaminerAssignmentsCubit>().filter(chip: 1),
                    selectedColor: const Color(0xFF2563EB).withValues(alpha: 0.2),
                  ),
                  FilterChip(
                    label: const Text('مكتمل'),
                    selected: currentChip == 2,
                    onSelected: (_) => context.read<ExaminerAssignmentsCubit>().filter(chip: 2),
                    selectedColor: const Color(0xFF2563EB).withValues(alpha: 0.2),
                  ),
                ],
              ),
            ),
            items: items,
            itemBuilder: (row) => Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              child: ListTile(
                title: Text(row['student_name']?.toString() ?? ''),
                subtitle: Text('${row['test_title'] ?? ''}\n${row['halaqah_name'] ?? ''} · ${row['status'] ?? ''}'),
                isThreeLine: true,
                trailing: row['has_result'] == true
                    ? const Icon(Icons.check_circle, color: Color(0xFF059669))
                    : const Icon(Icons.pending_outlined, color: Colors.orange),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ==========================================
// 3. قائمة النتائج
// ==========================================
class ExaminerResultsListPage extends StatelessWidget {
  const ExaminerResultsListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExaminerResultsCubit(
        ExaminerRepositoryImpl(apiClient: ApiClient(tokenStorage: SecureTokenStorage())),
      )..load(),
      child: BlocBuilder<ExaminerResultsCubit, ExaminerResultsState>(
        builder: (context, state) {
          final isLoading = state is ResultsLoading;
          final error = state is ResultsError ? state.message : null;
          final items = state is ResultsLoaded ? state.filtered : <Map<String, dynamic>>[];

          return _SharedListScaffold(
            title: 'نتائج الاختبارات',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<ExaminerResultsCubit>().load(),
            onSearch: (q) => context.read<ExaminerResultsCubit>().search(q),
            items: items,
            itemBuilder: (row) => Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              child: ListTile(
                title: Text(row['student_name']?.toString() ?? ''),
                subtitle: Text('${row['test_title'] ?? ''}\nالمستوى: ${row['level'] ?? '-'} · المجموع: ${row['total_score'] ?? '-'}'),
                isThreeLine: true,
              ),
            ),
          );
        },
      ),
    );
  }
}