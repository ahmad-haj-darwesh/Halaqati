import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_error_view.dart' show AppErrorView, AppLoadingView;
import '../data/teacher_students_repository.dart';
import 'bloc/students_cubit.dart';
import 'student_profile_page.dart';

/// شاشة قائمة طلاب الحلقة للمعلم (مُحدّثة للعمل بنظام Cubit).
class StudentsPage extends StatelessWidget {
  final TeacherStudentsRepository? repository;

  const StudentsPage({super.key, this.repository});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => StudentsCubit(
        repository ?? sl<TeacherStudentsRepository>(),
      )..loadStudents(),
      child: const _StudentsView(),
    );
  }
}

class _StudentsView extends StatelessWidget {
  const _StudentsView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('طلاب الحلقة'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              decoration: const InputDecoration(
                hintText: 'بحث بالاسم...',
                prefixIcon: Icon(Icons.search_rounded),
              ),
              onChanged: (val) => context.read<StudentsCubit>().search(val),
            ),
          ),
          Expanded(
            child: BlocBuilder<StudentsCubit, StudentsState>(
              builder: (context, state) {
                if (state is StudentsLoading) {
                  return const AppLoadingView(message: 'جاري تحميل الطلاب...');
                }
                if (state is StudentsError) {
                  return AppErrorView(
                    message: state.message, 
                    onRetry: () => context.read<StudentsCubit>().loadStudents()
                  );
                }
                
                final students = (state as StudentsLoaded).filteredStudents;
                
                if (students.isEmpty) {
                  return const Center(child: Text('لا توجد نتائج'));
                }

                return RefreshIndicator(
                  color: AppColors.forest,
                  onRefresh: () => context.read<StudentsCubit>().loadStudents(),
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    itemCount: students.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, i) {
                      final s = students[i];
                      return _StudentTile(student: s);
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _StudentTile extends StatelessWidget {
  final StudentListItemDto student;
  const _StudentTile({required this.student});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surfaceCard,
      borderRadius: BorderRadius.circular(16),
      elevation: 1,
      shadowColor: Colors.black12,
      child: ListTile(
        onTap: () async {
          await Navigator.push<void>(
            context, 
            MaterialPageRoute(builder: (_) => StudentProfilePage(studentId: student.id))
          );
          if (context.mounted) context.read<StudentsCubit>().loadStudents();
        },
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: _studentAvatar(student),
        title: Text(
          student.fullName,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: student.enrollment != null
            ? Text('الحالة: ${student.enrollment!['status'] ?? '-'}')
            : null,
        trailing: Icon(
          student.isActive ? Icons.check_circle_rounded : Icons.pause_circle_rounded,
          color: student.isActive ? AppColors.mint : Colors.grey.shade400,
          size: 22,
        ),
      ),
    );
  }
}

// ----------------------------------------------------------------------
// الدوال المساعدة (بنيتها الأصلية كما طلبت)
// ----------------------------------------------------------------------

/// استخراج أول حرف من الاسم لعرضه كـ avatar بديل.
String _firstChar(String name) {
  if (name.trim().isEmpty) return '?';
  return name.trim().substring(0, 1);
}

/// بناء صورة الطالب في القائمة (شبكة أو حرف).
Widget _studentAvatar(StudentListItemDto s) {
  final resolved = ApiConstants.resolvePublicMediaUrl(s.photoUrl);
  return CircleAvatar(
    radius: 22,
    backgroundColor: AppColors.mint.withValues(alpha: 0.15),
    foregroundColor: AppColors.forest,
    child: resolved.isNotEmpty
        ? ClipOval(
            child: Image.network(
              resolved,
              width: 44,
              height: 44,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Text(
                _firstChar(s.fullName),
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
            ),
          )
        : Text(
            _firstChar(s.fullName),
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
  );
}