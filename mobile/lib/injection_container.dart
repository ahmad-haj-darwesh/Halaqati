import 'package:get_it/get_it.dart';

// 1. Core & Services
import 'storage/token_storage.dart';
import 'services/api/api_client.dart';
import 'core/offline/cache_dao.dart';
import 'core/offline/caching_api_client.dart';
import 'core/offline/connectivity_service.dart';
import 'core/offline/offline_database.dart';
import 'core/offline/offline_gateway.dart';
import 'core/offline/outbox_dao.dart';
import 'core/offline/sync_manager.dart';
import 'features/examiner/data/offline_examiner_repository.dart';
import 'features/home/data/offline_teacher_daily_repository.dart';
import 'features/supervisor/data/offline_supervisor_repository.dart';

// 2. Auth & Notifications
import 'features/auth/data/auth_repository.dart';
import 'features/auth/presentation/bloc/login_cubit.dart';
import 'features/notifications/presentation/bloc/notifications_cubit.dart';

// 3. Teacher Features
import 'features/home/data/teacher_daily_repository.dart';
import 'features/home/data/teacher_own_profile_repository.dart';
import 'features/home/data/teacher_student_profile_repository.dart';
import 'features/home/data/teacher_students_repository.dart';
import 'features/home/presentation/bloc/daily_record_cubit.dart';
import 'features/home/presentation/bloc/monthly_report_cubit.dart';
import 'features/home/presentation/bloc/student_profile_cubit.dart';
import 'features/home/presentation/bloc/students_cubit.dart';
import 'features/home/presentation/bloc/teacher_home_cubit.dart';

// 4. Supervisor Features
import 'features/supervisor/data/supervisor_repository.dart';
import 'features/supervisor/presentation/bloc/attendance_stats_cubit.dart';
import 'features/supervisor/presentation/bloc/halaqah_daily_cubit.dart';
import 'features/supervisor/presentation/bloc/my_visits_cubit.dart';
import 'features/supervisor/presentation/bloc/supervisory_visit_form_cubit.dart';
import 'features/supervisor/presentation/bloc/teacher_detail_cubit.dart';
import 'features/supervisor/presentation/bloc/supervisor_home_cubit.dart';
import 'features/supervisor/presentation/bloc/supervisor_list_cubits.dart'; 

// 5. Examiner Features
import 'features/examiner/data/examiner_repository.dart';
import 'features/examiner/presentation/bloc/exam_daily_summary_cubit.dart';
import 'features/examiner/presentation/bloc/exam_test_stepper_cubit.dart';
import 'features/examiner/presentation/bloc/examiner_home_cubit.dart';
import 'features/examiner/presentation/bloc/examiner_list_cubits.dart'; 
import 'features/examiner/presentation/bloc/examiner_shell_cubit.dart';
import 'features/examiner/presentation/bloc/examiner_student_detail_cubit.dart';

final sl = GetIt.instance; // sl = Service Locator

void initDependencies() {
  // ==========================================
  // 1. الأساسيات (Core)
  // ==========================================
  sl.registerLazySingleton<TokenStorage>(() => SecureTokenStorage());

  // ==========================================
  // 1.b طبقة العمل دون إنترنت (Offline)
  // ==========================================
  sl.registerLazySingleton<OfflineDatabase>(() => OfflineDatabase());
  sl.registerLazySingleton<CacheDao>(() => CacheDao(sl()));
  sl.registerLazySingleton<OutboxDao>(() => OutboxDao(sl()));
  sl.registerLazySingleton<ConnectivityService>(() => ConnectivityPlusService());

  // عميل واحد يخدم التطبيق كله: كل طلب GET يُخزَّن ويُقرأ من الكاش عند انقطاع الشبكة،
  // فتعمل شاشات القراءة دون إنترنت بلا تعديل في أي مستودع.
  sl.registerLazySingleton<ApiClient>(
    () => CachingApiClient(tokenStorage: sl<TokenStorage>(), cache: sl<CacheDao>()),
  );

  sl.registerLazySingleton<SyncManager>(() => SyncManager(
        outbox: sl(),
        apiClient: sl(),
        connectivity: sl(),
        tokenStorage: sl(),
      ));

  sl.registerLazySingleton<OfflineGateway>(() => OfflineGateway(
        outbox: sl(),
        tokenStorage: sl(),
        syncManager: sl(),
      ));

  // ==========================================
  // 2. المستودعات (Repositories)
  // ==========================================
  sl.registerLazySingleton<AuthRepository>(() => AuthRepository(apiClient: sl(), tokenStorage: sl()));

  // المستودعات الثلاثة التي تكتب بيانات ميدانية تُغلَّف بطبقة الطابور؛ أما مستودعات
  // القراءة فتبقى كما هي لأن الكاش يعمل تحتها في CachingApiClient.
  sl.registerLazySingleton<TeacherDailyRepository>(() => OfflineTeacherDailyRepository(
        inner: TeacherDailyRepositoryImpl(apiClient: sl()),
        gateway: sl(),
      ));
  sl.registerLazySingleton<SupervisorRepository>(() => OfflineSupervisorRepository(
        inner: SupervisorRepositoryImpl(apiClient: sl()),
        gateway: sl(),
      ));
  sl.registerLazySingleton<ExaminerRepository>(() => OfflineExaminerRepository(
        inner: ExaminerRepositoryImpl(apiClient: sl()),
        gateway: sl(),
      ));

  sl.registerLazySingleton<TeacherOwnProfileRepository>(() => TeacherOwnProfileRepositoryImpl(apiClient: sl()));
  sl.registerLazySingleton<TeacherStudentProfileRepository>(() => TeacherStudentProfileRepositoryImpl(apiClient: sl()));
  sl.registerLazySingleton<TeacherStudentsRepository>(() => TeacherStudentsRepositoryImpl(apiClient: sl()));

  // ==========================================
  // 3. مديرو الحالة (Cubits)
  // ==========================================
  // Auth & Notifications
  sl.registerFactory(() => LoginCubit(sl()));
  sl.registerFactory(() => NotificationsCubit(sl()));

  // Teacher
  sl.registerFactory(() => TeacherHomeCubit(sl()));
  sl.registerFactory(() => DailyRecordCubit(sl()));
  sl.registerFactory(() => MonthlyReportCubit(sl()));
  sl.registerFactory(() => StudentsCubit(sl()));
  sl.registerFactoryParam<StudentProfileCubit, int, void>((studentId, _) => StudentProfileCubit(sl(), studentId));

  // Supervisor
  sl.registerFactory(() => SupervisorHomeCubit(sl()));
  sl.registerFactory(() => AttendanceStatsCubit(sl()));
  sl.registerFactory(() => MyVisitsCubit(sl()));
  sl.registerFactory(() => SupervisoryVisitFormCubit(sl()));
  sl.registerFactory(() => SupervisorCentersCubit(sl()));
  sl.registerFactory(() => SupervisorTeachersCubit(sl()));
  sl.registerFactory(() => SupervisorHalaqahsCubit(sl(), sl())); 
  sl.registerFactoryParam<HalaqahDailyCubit, int, void>((halaqahId, _) => HalaqahDailyCubit(repo: sl(), halaqahId: halaqahId));
  sl.registerFactoryParam<TeacherDetailCubit, int, void>((teacherId, _) => TeacherDetailCubit(sl()));

  // Examiner
  sl.registerFactory(() => ExaminerHomeCubit(sl()));
  sl.registerFactory(() => ExamDailySummaryCubit(sl()));
  sl.registerFactory(() => ExamTestStepperCubit(sl()));
  sl.registerFactory(() => ExaminerTestsCubit(sl()));
  sl.registerFactory(() => ExaminerAssignmentsCubit(sl()));
  sl.registerFactory(() => ExaminerResultsCubit(sl()));
  sl.registerFactory(() => ExaminerShellCubit());
  sl.registerFactory(() => ExaminerStudentDetailCubit(sl()));
}