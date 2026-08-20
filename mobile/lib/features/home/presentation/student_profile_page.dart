import 'package:flutter/material.dart';
import '../../../injection_container.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_error_view.dart' show AppErrorView, AppLoadingView;
import '../data/teacher_student_profile_repository.dart';
import 'bloc/student_profile_cubit.dart';

/// شاشة ملف الطالب (Student Profile) للمعلم (مُحدّثة لتعمل بنظام Cubit).
class StudentProfilePage extends StatelessWidget {
  final int studentId;
  const StudentProfilePage({super.key, required this.studentId});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => StudentProfileCubit(
        sl<TeacherStudentProfileRepository>(),
        studentId,
      )..load(),
      child: const _StudentProfileView(),
    );
  }
}

class _StudentProfileView extends StatefulWidget {
  const _StudentProfileView();

  @override
  State<_StudentProfileView> createState() => _StudentProfileViewState();
}

class _StudentProfileViewState extends State<_StudentProfileView> {
  final _formKey = GlobalKey<FormState>();
  final _fullName = TextEditingController();
  final _guardianName = TextEditingController();
  final _guardianPhone = TextEditingController();
  final _nationalId = TextEditingController();
  final _notes = TextEditingController();

  String _gender = 'male';
  DateTime? _birthDate;

  @override
  void dispose() {
    _fullName.dispose();
    _guardianName.dispose();
    _guardianPhone.dispose();
    _nationalId.dispose();
    _notes.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('ملف الطالب')),
      body: BlocConsumer<StudentProfileCubit, StudentProfileState>(
        listener: (context, state) {
          if (state is ProfileError) {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.message)));
          }
        },
        builder: (context, state) {
          if (state is ProfileLoading) return const AppLoadingView(message: 'جاري تحميل الملف...');
          if (state is ProfileError) return AppErrorView(message: state.message, onRetry: () => context.read<StudentProfileCubit>().load());

          final loaded = state as ProfileLoaded;
          final st = loaded.data;
          final s = st.student;
          final editable = st.canEdit;
          final pending = st.pendingSubmission != null;

          // ملء البيانات عند التحميل لأول مرة
          if (_fullName.text.isEmpty) {
            _fullName.text = s.fullName;
            _guardianName.text = s.guardianName ?? '';
            _guardianPhone.text = s.guardianPhone ?? '';
            _nationalId.text = s.nationalId ?? '';
            _notes.text = s.notes ?? '';
            _gender = s.gender;
            _birthDate = s.birthDate != null ? DateTime.tryParse(s.birthDate!) : null;
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (pending) _buildInfoBanner('يوجد طلب قيد مراجعة المشرف. لا يمكن تعديل الملف.', Icons.hourglass_top_rounded, const Color(0xFFF57C00), const Color(0xFFFFF8E1)),
                  if (!editable && !pending) _buildInfoBanner(s.profileLocked ? 'الملف معتمد.' : 'لا يمكن تعديل الملف حالياً.', Icons.lock_rounded, AppColors.forest, AppColors.mint.withValues(alpha: 0.2)),
                  
                  _buildAvatarSection(context, loaded, s, editable),
                  
                  const SizedBox(height: 20),
                  TextFormField(controller: _fullName, decoration: const InputDecoration(labelText: 'الاسم الكامل'), readOnly: !editable, validator: (v) => (v == null || v.trim().isEmpty) ? 'الاسم مطلوب' : null),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _gender,
                    decoration: const InputDecoration(labelText: 'الجنس'),
                    items: const [DropdownMenuItem(value: 'male', child: Text('ذكر')), DropdownMenuItem(value: 'female', child: Text('أنثى'))],
                    onChanged: editable ? (v) => setState(() => _gender = v!) : null,
                  ),
                  const SizedBox(height: 12),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('تاريخ الميلاد'),
                    subtitle: Text(_birthDate == null ? 'غير محدد' : '${_birthDate!.year}/${_birthDate!.month}/${_birthDate!.day}'),
                    trailing: editable ? IconButton(onPressed: () async {
                      final d = await showDatePicker(context: context, initialDate: _birthDate ?? DateTime(2010), firstDate: DateTime(1990), lastDate: DateTime.now());
                      if (d != null) setState(() => _birthDate = d);
                    }, icon: const Icon(Icons.calendar_today_rounded)) : null,
                  ),
                  TextFormField(controller: _guardianName, decoration: const InputDecoration(labelText: 'اسم ولي الأمر'), readOnly: !editable),
                  const SizedBox(height: 12),
                  TextFormField(controller: _guardianPhone, decoration: const InputDecoration(labelText: 'هاتف ولي الأمر'), keyboardType: TextInputType.phone, readOnly: !editable),
                  const SizedBox(height: 12),
                  TextFormField(controller: _nationalId, decoration: const InputDecoration(labelText: 'الهوية الوطنية'), readOnly: !editable),
                  const SizedBox(height: 12),
                  TextFormField(controller: _notes, decoration: const InputDecoration(labelText: 'ملاحظات'), maxLines: 4, readOnly: !editable),
                  const SizedBox(height: 24),
                  
                  if (editable)
                    FilledButton.icon(
                      onPressed: loaded.isSaving ? null : () {
                        if (!_formKey.currentState!.validate()) return;
                        context.read<StudentProfileCubit>().save(
                          fullName: _fullName.text, gender: _gender, birthDate: _birthDate?.toString(),
                          guardianName: _guardianName.text, guardianPhone: _guardianPhone.text,
                          nationalId: _nationalId.text, notes: _notes.text,
                        );
                      },
                      icon: loaded.isSaving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.save_rounded),
                      label: Text(loaded.isSaving ? 'جاري الحفظ...' : 'حفظ'),
                    ),
                  if (st.canSubmit && !pending) ...[
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: loaded.isSubmitting ? null : () => context.read<StudentProfileCubit>().submit(),
                      icon: loaded.isSubmitting ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send_rounded),
                      label: Text(loaded.isSubmitting ? 'جاري الإرسال...' : 'إرسال الملف للمراجعة'),
                    ),
                  ]
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInfoBanner(String text, IconData icon, Color iconColor, Color bgColor) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: Material(color: bgColor, borderRadius: BorderRadius.circular(12), child: Padding(padding: const EdgeInsets.all(12), child: Row(children: [Icon(icon, color: iconColor), const SizedBox(width: 8), Expanded(child: Text(text, style: const TextStyle(fontSize: 13)))]))),
  );

  Widget _buildAvatarSection(BuildContext context, ProfileLoaded state, dynamic student, bool editable) {
    return Center(
      child: Column(
        children: [
          CircleAvatar(
            radius: 56,
            backgroundColor: AppColors.mint.withValues(alpha: 0.2),
            child: ClipOval(
              child: state.pickedPhoto != null
                  ? Image.network(state.pickedPhoto!.path, width: 112, height: 112, fit: BoxFit.cover)
                  : _renderAvatarImage(student),
            ),
          ),
          if (editable) ...[
            const SizedBox(height: 10),
            FilledButton.tonalIcon(
              onPressed: () async {
                final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 1600, imageQuality: 88);
                if (x != null && mounted) context.read<StudentProfileCubit>().pickPhoto(x);
              },
              icon: const Icon(Icons.photo_camera_outlined, size: 18),
              label: const Text('تغيير الصورة'),
            ),
          ],
        ],
      ),
    );
  }

  Widget _renderAvatarImage(dynamic student) {
    final url = ApiConstants.resolvePublicMediaUrl(student.photoUrl);
    if (url.isEmpty) return _avatarInitial(student.fullName);
    return Image.network(url, width: 112, height: 112, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _avatarInitial(student.fullName));
  }

  Widget _avatarInitial(String fullName) {
    final ch = fullName.trim().isEmpty ? '?' : fullName.trim().substring(0, 1);
    return SizedBox(width: 112, height: 112, child: Center(child: Text(ch, style: const TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: AppColors.forest))));
  }
}