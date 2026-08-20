import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../data/supervisor_repository.dart';
import '../pages/halaqah_daily_page.dart';
import '../pages/teacher_detail_page.dart';
import 'bloc/supervisor_list_cubits.dart';

const _g0 = Color(0xFF2563EB);

/// واجهة Scaffold مشتركة (Shared UI)
class _SharedSupervisorListScaffold extends StatelessWidget {
  final String title;
  final bool isLoading;
  final String? error;
  final VoidCallback onRefresh;
  final ValueChanged<String> onSearch;
  final List<Map<String, dynamic>> items;
  final Widget Function(Map<String, dynamic> row) itemBuilder;

  const _SharedSupervisorListScaffold({
    required this.title,
    required this.isLoading,
    this.error,
    required this.onRefresh,
    required this.onSearch,
    required this.items,
    required this.itemBuilder,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              onChanged: onSearch,
              textAlign: TextAlign.right,
              decoration: InputDecoration(
                hintText: 'بحث…',
                prefixIcon: const Icon(Icons.search_rounded),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                isDense: true,
              ),
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: _g0));
    }
    if (error != null) {
      return ListView(
        children: [
          const SizedBox(height: 80),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Text(error!, textAlign: TextAlign.center),
          ),
          Center(child: FilledButton(onPressed: onRefresh, child: const Text('إعادة المحاولة'))),
        ],
      );
    }
    if (items.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 120),
          Center(child: Text('لا توجد نتائج')),
        ],
      );
    }
    return RefreshIndicator(
      color: _g0,
      onRefresh: () async => onRefresh(),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (_, i) => itemBuilder(items[i]),
      ),
    );
  }
}

// ==========================================
// 1. قائمة المراكز
// ==========================================
class SupervisorCentersListPage extends StatelessWidget {
  const SupervisorCentersListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final repo = sl<SupervisorRepository>();
        return SupervisorCentersCubit(repo)..load();
      },
      child: BlocBuilder<SupervisorCentersCubit, SupervisorCentersState>(
        builder: (context, state) {
          final isLoading = state is CentersLoading;
          final error = state is CentersError ? state.message : null;
          final items = state is CentersLoaded ? state.filtered : <Map<String, dynamic>>[];

          return _SharedSupervisorListScaffold(
            title: 'المراكز',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<SupervisorCentersCubit>().load(),
            onSearch: (q) => context.read<SupervisorCentersCubit>().search(q),
            items: items,
            itemBuilder: (row) => Card(
              child: ListTile(
                title: Text(row['name']?.toString() ?? ''),
                subtitle: Text(row['region_name']?.toString() ?? ''),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ==========================================
// 2. قائمة الحلقات
// ==========================================
class SupervisorHalaqahsListPage extends StatelessWidget {
  const SupervisorHalaqahsListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = sl<ApiClient>();
        final repo = sl<SupervisorRepository>();
        return SupervisorHalaqahsCubit(repo, apiClient)..load();
      },
      child: BlocBuilder<SupervisorHalaqahsCubit, SupervisorHalaqahsState>(
        builder: (context, state) {
          final isLoading = state is HalaqahsLoading;
          final error = state is HalaqahsError ? state.message : null;
          final items = state is HalaqahsLoaded ? state.filtered : <Map<String, dynamic>>[];
          final attendanceMap = state is HalaqahsLoaded ? state.attendanceMap : <int, int>{};

          return _SharedSupervisorListScaffold(
            title: 'الحلقات',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<SupervisorHalaqahsCubit>().load(),
            onSearch: (q) => context.read<SupervisorHalaqahsCubit>().search(q),
            items: items,
            itemBuilder: (row) {
              final id = row['id'] as int;
              final rate = attendanceMap[id];
              return Card(
                child: ListTile(
                  title: Text(row['name']?.toString() ?? ''),
                  subtitle: Text(
                    '${row['center_name'] ?? ''}\n${row['teacher_name'] ?? ''}',
                    style: const TextStyle(height: 1.35),
                  ),
                  isThreeLine: true,
                  trailing: rate != null
                      ? Chip(
                          label: Text('$rate٪'),
                          backgroundColor: _g0.withValues(alpha: 0.12),
                          labelStyle: const TextStyle(color: _g0, fontWeight: FontWeight.w700),
                        )
                      : null,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute<void>(
                        builder: (_) => HalaqahDailyPage(
                          halaqahId: id,
                          halaqahName: row['name']?.toString() ?? '',
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}

// ==========================================
// 3. قائمة المعلمين
// ==========================================
class SupervisorTeachersListPage extends StatelessWidget {
  const SupervisorTeachersListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final repo = sl<SupervisorRepository>();
        return SupervisorTeachersCubit(repo)..load();
      },
      child: BlocBuilder<SupervisorTeachersCubit, SupervisorTeachersState>(
        builder: (context, state) {
          final isLoading = state is TeachersLoading;
          final error = state is TeachersError ? state.message : null;
          final items = state is TeachersLoaded ? state.filtered : <Map<String, dynamic>>[];

          return _SharedSupervisorListScaffold(
            title: 'المعلّمون',
            isLoading: isLoading,
            error: error,
            onRefresh: () => context.read<SupervisorTeachersCubit>().load(),
            onSearch: (q) => context.read<SupervisorTeachersCubit>().search(q),
            items: items,
            itemBuilder: (row) {
              final userId = row['user_id'] as int;
              final name = row['teacher_name']?.toString() ?? '';
              final last = row['last_visit_date'] as String?;
              final avg = row['avg_visit_score'];
              return Card(
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _g0.withValues(alpha: 0.12),
                    child: Text(
                      name.isNotEmpty ? name.substring(0, 1) : '?',
                      style: const TextStyle(color: _g0, fontWeight: FontWeight.w800),
                    ),
                  ),
                  title: Text(name),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(row['halaqah_name']?.toString() ?? ''),
                      const SizedBox(height: 4),
                      Text(
                        last != null ? 'آخر زيارة: $last' : 'لم يُزر بعد',
                        style: TextStyle(
                          color: last != null ? Colors.grey.shade700 : Colors.orange.shade800,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                  isThreeLine: true,
                  trailing: avg != null
                      ? Chip(
                          label: Text(avg.toString()),
                          backgroundColor: const Color(0xFF059669).withValues(alpha: 0.12),
                          labelStyle: const TextStyle(
                            color: Color(0xFF059669),
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        )
                      : null,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute<void>(
                        builder: (_) => TeacherDetailPage(
                          teacherId: userId,
                          teacherName: name,
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}