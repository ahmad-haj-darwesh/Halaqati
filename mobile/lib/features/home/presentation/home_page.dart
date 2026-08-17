import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../auth/presentation/login_page.dart';
import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../widgets/notification_bell_button.dart';
import '../../auth/data/auth_repository.dart';
import '../data/teacher_own_profile_repository.dart';
import 'bloc/teacher_home_cubit.dart'; // مسار الـ Cubit
import 'daily_record_page.dart';
import 'monthly_report_page.dart';
import 'students_page.dart';

/// ألوان مستوحاة من لوحات التحكم الحديثة (فاتحة + أزرق هادئ) مع دمج هوية حلقتي.
abstract final class _DashColors {
  static const Color bg = Color(0xFFF0F4FA);
  static const Color bannerStart = Color(0xFFE3F2FD);
  static const Color bannerEnd = Color(0xFFE8F5E9);
  static const Color accentBlue = Color(0xFF2563EB);
  static const Color textMuted = Color(0xFF64748B);
  static const Color cardBorder = Color(0xFFE2E8F0);
}

/// الصفحة الرئيسية للمعلم (Teacher Dashboard) بنظام Cubit.
class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    // 1. توفير الـ Cubit للشجرة وبدء تحميل البيانات فوراً
    return BlocProvider(
      create: (context) {
        final storage = SecureTokenStorage();
        final apiClient = ApiClient(tokenStorage: storage);
        final repo = TeacherOwnProfileRepositoryImpl(apiClient: apiClient);
        return TeacherHomeCubit(repo)..loadProfile();
      },
      child: const _HomePageView(),
    );
  }
}

class _HomePageView extends StatelessWidget {
  const _HomePageView();

  /// عرض حوار تأكيد تسجيل الخروج.
  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تسجيل الخروج'),
        content: const Text('هل تريد تسجيل الخروج من التطبيق؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFDC2626),
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('تسجيل الخروج'),
          ),
        ],
      ),
    );
    
    if (confirmed == true && context.mounted) {
      await _logout(context);
    }
  }

  /// تسجيل الخروج ثم إعادة المستخدم إلى صفحة الدخول.
  Future<void> _logout(BuildContext context) async {
    final storage = SecureTokenStorage();
    final authRepo = AuthRepository(
      apiClient: ApiClient(tokenStorage: storage), 
      tokenStorage: storage,
    );
    await authRepo.logout();
    
    if (context.mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const LoginPage()),
      );
    }
  }

  /// فتح صفحة فرعية ضمن Navigator.
  void _open(BuildContext context, Widget page) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => page));
  }

  /// فتح شاشة تسجيل اليوم وإظهار رسالة نجاح عند الرجوع.
  Future<void> _openDailyRecord(BuildContext context) async {
    final saved = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const DailyRecordPage()),
    );
    
    if (saved == true && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('تم حفظ التسجيلات'),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    }
  }

  /// تغيير صورة المعلم.
  Future<void> _pickTeacherPhoto(BuildContext context, bool canEdit, bool isUploading) async {
    if (!canEdit || isUploading) return;
    
    final picker = ImagePicker();
    final x = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1600,
      imageQuality: 88,
    );
    
    if (x != null && context.mounted) {
      // إرسال المسار للـ Cubit ليتولى الرفع وإدارة الحالة
      context.read<TeacherHomeCubit>().uploadPhoto(x.path);
    }
  }

  @override
  Widget build(BuildContext context) {
    // 2. استخدام BlocConsumer للاستماع للأخطاء وتحديث الواجهة
    return BlocConsumer<TeacherHomeCubit, TeacherHomeState>(
      listener: (context, state) {
        // إذا حدث خطأ (سواء في التحميل الأولي أو رفع الصورة)، نعرض SnackBar
        if (state is HomeError) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: Colors.red.shade800,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      },
      builder: (context, state) {
        // حالة التحميل الأولي
        if (state is HomeLoading) {
          return const Scaffold(
            backgroundColor: _DashColors.bg,
            body: Center(child: CircularProgressIndicator(color: AppColors.forest)),
          );
        }

        // إذا كان الخطأ في التحميل الأولي ولم توجد بيانات لعرضها
        if (state is HomeError && context.read<TeacherHomeCubit>().state is! HomeLoaded && context.read<TeacherHomeCubit>().state is! HomeUploadingPhoto) {
           // ملاحظة: الـ Cubit يرسل HomeError ثم يعود لـ HomeLoaded في حالة فشل الصورة،
           // لذا إذا توقفت الحالة عند HomeError فهذا يعني فشل التحميل الأولي.
          return Scaffold(
            backgroundColor: _DashColors.bg,
            body: _ErrorBody(
              message: state.message, 
              onRetry: () => context.read<TeacherHomeCubit>().loadProfile(),
            ),
          );
        }

        // استخراج البيانات بناءً على الحالة
        Map<String, dynamic>? profile;
        bool isUploadingPhoto = false;

        if (state is HomeLoaded) {
          profile = state.profile;
        } else if (state is HomeUploadingPhoto) {
          profile = state.profile;
          isUploadingPhoto = true;
        } else if (state is HomeError) {
           // في حال مررنا بحالة الخطأ بشكل مؤقت (كما يحدث عند فشل رفع الصورة)
           // ستتم استعادة البيانات فوراً لأن الـ Cubit يرسل HomeLoaded بعدها.
           return const Scaffold(backgroundColor: _DashColors.bg, body: SizedBox());
        }

        if (profile == null) {
          return const Scaffold(
            backgroundColor: _DashColors.bg,
            body: Center(child: Text('لا توجد بيانات')),
          );
        }

        return Scaffold(
          backgroundColor: _DashColors.bg,
          body: RefreshIndicator(
            color: AppColors.forest,
            onRefresh: () => context.read<TeacherHomeCubit>().loadProfile(),
            child: _buildScroll(context, profile, isUploadingPhoto),
          ),
        );
      },
    );
  }

  Widget _buildScroll(BuildContext context, Map<String, dynamic> profile, bool uploadingPhoto) {
    final name = profile['name']?.toString() ?? '';
    final email = profile['email']?.toString() ?? '';
    final photoUrl = profile['profile']?['photo_url']?.toString();
    final canEditPhoto = profile['can_edit_own_photo'] as bool? ?? false;
    final halaqah = profile['profile']?['halaqah'];
    final center = halaqah?['center'];
    final region = center?['region'];

    return CustomScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverToBoxAdapter(
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(16, 12, 16, 8),
              child: _DashboardTopBar(
                onRefresh: () => context.read<TeacherHomeCubit>().loadProfile(),
                onLogout: () => _confirmLogout(context),
              ),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 12),
            child: _SearchBarHint(
              onTap: () => _open(context, const StudentsPage()),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 16),
            child: _WelcomeBanner(
              userName: name,
              onPrimaryAction: () => _openDailyRecord(context),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 20),
            child: _ProfileHeroCard(
              name: name,
              email: email,
              photoUrl: photoUrl,
              canEditPhoto: canEditPhoto,
              uploadingPhoto: uploadingPhoto,
              onEditPhoto: canEditPhoto 
                  ? () => _pickTeacherPhoto(context, canEditPhoto, uploadingPhoto) 
                  : null,
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 8),
            child: const _SectionTitle(title: 'إدارة الحلقة', subtitle: 'اختصاراتك اليومية'),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 8),
            child: _ModernActionCard(
              icon: Icons.fact_check_rounded,
              title: 'تسجيل اليوم',
              subtitle: 'الحضور، التقييم، الحفظ والملاحظات',
              accent: AppColors.forest,
              onTap: () => _openDailyRecord(context),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 8),
            child: _ModernActionCard(
              icon: Icons.groups_rounded,
              title: 'طلاب الحلقة',
              subtitle: 'قائمة الطلاب والملفات الشخصية',
              accent: _DashColors.accentBlue,
              onTap: () => _open(context, const StudentsPage()),
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 16),
            child: _ModernActionCard(
              icon: Icons.insights_rounded,
              title: 'تقرير الشهر',
              subtitle: 'إحصاءات الحضور والتميّز',
              accent: const Color(0xFF7C3AED),
              onTap: () => _open(context, const MonthlyReportPage()),
            ),
          ),
        ),
        if (halaqah != null) ...[
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(16, 8, 16, 8),
              child: const _SectionTitle(title: 'مكان العمل', subtitle: null),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 16),
              child: _WorkplaceCard(
                halaqahName: halaqah['name']?.toString() ?? '',
                centerName: center?['name']?.toString(),
                regionName: region?['name']?.toString(),
              ),
            ),
          ),
        ],
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(16, 0, 16, 32),
            child: _TipCard(
              onOpenDaily: () => _openDailyRecord(context),
            ),
          ),
        ),
      ],
    );
  }
}

// ----------------------------------------------------------------------
// الويدجت المستقلة (بقيت كما هي، فقط تم تحويلها للعمل داخل Stateless Context)
// ----------------------------------------------------------------------

class _DashboardTopBar extends StatelessWidget {
  final VoidCallback onRefresh;
  final VoidCallback onLogout;

  const _DashboardTopBar({
    required this.onRefresh,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: const Icon(Icons.menu_book_rounded, color: AppColors.forest, size: 22),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'حلقتي',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: AppColors.deepGreen,
                      letterSpacing: -0.5,
                    ),
              ),
              Text(
                'لوحة المعلّم',
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        const NotificationBellButton(iconColor: AppColors.forest),
        IconButton.filledTonal(
          style: IconButton.styleFrom(
            backgroundColor: Colors.white,
            foregroundColor: AppColors.forest,
          ),
          onPressed: onRefresh,
          tooltip: 'تحديث',
          icon: const Icon(Icons.refresh_rounded),
        ),
        const SizedBox(width: 4),
        IconButton.filledTonal(
          style: IconButton.styleFrom(
            backgroundColor: Colors.white,
            foregroundColor: const Color(0xFFDC2626),
          ),
          onPressed: onLogout,
          tooltip: 'تسجيل الخروج',
          icon: const Icon(Icons.power_settings_new_rounded),
        ),
      ],
    );
  }
}

class _SearchBarHint extends StatelessWidget {
  final VoidCallback onTap;

  const _SearchBarHint({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      elevation: 0,
      shadowColor: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: _DashColors.cardBorder),
          ),
          child: Row(
            children: [
              Icon(Icons.search_rounded, color: Colors.grey.shade400, size: 22),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'بحث سريع عن طالب…',
                  style: TextStyle(
                    color: Colors.grey.shade500,
                    fontSize: 15,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _WelcomeBanner extends StatelessWidget {
  final String userName;
  final VoidCallback onPrimaryAction;

  const _WelcomeBanner({
    required this.userName,
    required this.onPrimaryAction,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          begin: AlignmentDirectional.topStart,
          end: AlignmentDirectional.bottomEnd,
          colors: [
            _DashColors.bannerStart,
            _DashColors.bannerEnd,
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: _DashColors.accentBlue.withValues(alpha: 0.08),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'مرحباً، $userName',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'سجّل حضور الحلقة وتقييم الطلاب في خطوة واحدة.',
                  style: TextStyle(
                    fontSize: 14,
                    height: 1.45,
                    color: Colors.blueGrey.shade700,
                  ),
                ),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: onPrimaryAction,
                  icon: const Icon(Icons.edit_calendar_rounded, size: 20),
                  label: const Text('تسجيل اليوم'),
                  style: FilledButton.styleFrom(
                    backgroundColor: _DashColors.accentBlue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.85),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Icon(
              Icons.mosque_rounded,
              size: 56,
              color: AppColors.forest.withValues(alpha: 0.85),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfileHeroCard extends StatelessWidget {
  final String name;
  final String email;
  final String? photoUrl;
  final bool canEditPhoto;
  final bool uploadingPhoto;
  final VoidCallback? onEditPhoto;

  const _ProfileHeroCard({
    required this.name,
    required this.email,
    this.photoUrl,
    this.canEditPhoto = false,
    this.uploadingPhoto = false,
    this.onEditPhoto,
  });

  @override
  Widget build(BuildContext context) {
    final rawPhoto = photoUrl?.trim() ?? '';
    final resolvedPhoto = rawPhoto.isNotEmpty ? ApiConstants.resolvePublicMediaUrl(rawPhoto) : '';

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: _DashColors.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: canEditPhoto && !uploadingPhoto ? onEditPhoto : null,
                  customBorder: const CircleBorder(),
                  child: CircleAvatar(
                    radius: 34,
                    backgroundColor: AppColors.mint.withValues(alpha: 0.2),
                    backgroundImage: resolvedPhoto.isNotEmpty ? NetworkImage(resolvedPhoto) : null,
                    child: resolvedPhoto.isEmpty ? const Icon(Icons.person_rounded, size: 36, color: AppColors.forest) : null,
                  ),
                ),
              ),
              if (uploadingPhoto)
                const Positioned.fill(
                  child: CircleAvatar(
                    radius: 34,
                    backgroundColor: Colors.black26,
                    child: SizedBox(
                      width: 26,
                      height: 26,
                      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                    ),
                  ),
                ),
              if (canEditPhoto && !uploadingPhoto)
                PositionedDirectional(
                  bottom: 0,
                  end: 0,
                  child: Material(
                    color: AppColors.forest,
                    shape: const CircleBorder(),
                    elevation: 2,
                    child: InkWell(
                      onTap: onEditPhoto,
                      customBorder: const CircleBorder(),
                      child: const Padding(
                        padding: EdgeInsets.all(6),
                        child: Icon(Icons.camera_alt_rounded, size: 16, color: Colors.white),
                      ),
                    ),
                  ),
                )
              else if (!canEditPhoto)
                PositionedDirectional(
                  bottom: 2,
                  end: 2,
                  child: Container(
                    width: 14,
                    height: 14,
                    decoration: BoxDecoration(
                      color: const Color(0xFF22C55E),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.mint.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'معلّم',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.forest,
                    ),
                  ),
                ),
                if (email.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    email,
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                    textDirection: TextDirection.ltr,
                    textAlign: TextAlign.right,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final String? subtitle;

  const _SectionTitle({required this.title, this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w800,
            color: Color(0xFF0F172A),
          ),
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Text(
            subtitle!,
            style: const TextStyle(fontSize: 13, color: _DashColors.textMuted),
          ),
        ],
      ],
    );
  }
}

class _ModernActionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color accent;
  final VoidCallback onTap;

  const _ModernActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.accent,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: _DashColors.cardBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: accent, size: 26),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 13,
                        color: _DashColors.textMuted,
                        height: 1.35,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_left_rounded,
                color: Colors.grey.shade400,
                size: 28,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _WorkplaceCard extends StatelessWidget {
  final String halaqahName;
  final String? centerName;
  final String? regionName;

  const _WorkplaceCard({
    required this.halaqahName,
    this.centerName,
    this.regionName,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _DashColors.cardBorder),
      ),
      child: Column(
        children: [
          _WorkRow(
            icon: Icons.menu_book_rounded,
            label: 'الحلقة',
            value: halaqahName,
            color: AppColors.forest,
          ),
          if (centerName != null && centerName!.isNotEmpty) ...[
            Divider(height: 22, color: Colors.grey.shade200),
            _WorkRow(
              icon: Icons.apartment_rounded,
              label: 'المركز',
              value: centerName!,
              color: _DashColors.accentBlue,
            ),
          ],
          if (regionName != null && regionName!.isNotEmpty) ...[
            Divider(height: 22, color: Colors.grey.shade200),
            _WorkRow(
              icon: Icons.map_rounded,
              label: 'المنطقة',
              value: regionName!,
              color: const Color(0xFFEA580C),
            ),
          ],
        ],
      ),
    );
  }
}

class _WorkRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _WorkRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: color, size: 22),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(fontSize: 12, color: _DashColors.textMuted),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _TipCard extends StatelessWidget {
  final VoidCallback onOpenDaily;

  const _TipCard({required this.onOpenDaily});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.forest.withValues(alpha: 0.08),
            AppColors.mint.withValues(alpha: 0.06),
          ],
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.mint.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.lightbulb_outline_rounded, color: AppColors.forest),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'نصيحة سريعة',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                ),
                const SizedBox(height: 4),
                Text(
                  'سجّل اليومية قبل نهاية اليوم لتوثيق أدق للحلقة.',
                  style: TextStyle(fontSize: 13, color: Colors.blueGrey.shade800, height: 1.35),
                ),
              ],
            ),
          ),
          TextButton(
            onPressed: onOpenDaily,
            child: const Text('اذهب'),
          ),
        ],
      ),
    );
  }
}

class _ErrorBody extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorBody({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline_rounded, size: 48, color: Colors.red.shade400),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      ),
    );
  }
}