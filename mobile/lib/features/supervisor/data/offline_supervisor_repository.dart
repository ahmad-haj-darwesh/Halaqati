import 'package:uuid/uuid.dart';

import '../../../core/constants/api_constants.dart';
import '../../../core/offline/offline_gateway.dart';
import 'supervisor_models.dart';
import 'supervisor_repository.dart';

/// مغلّف يضيف الكتابة المؤجلة لتسجيل الزيارات الميدانية.
///
/// Arabic: يولّد `client_uuid` لكل زيارة قبل الإرسال. هذا المعرّف هو ما يجعل العملية
/// آمنة للتكرار على الخادم: لو وصل الطلب وحُفظت الزيارة ثم انقطع الاتصال قبل وصول
/// الرد، فإن إعادة الإرسال بنفس المعرّف تُعيد الزيارة نفسها بدل إنشاء زيارة ثانية
/// وإشعار ثانٍ للمعلّم.
/// EN: Deferred writes for field visits, with a per-visit `client_uuid` that makes
/// the request safe to replay after a dropped response.
class OfflineSupervisorRepository implements SupervisorRepository {
  final SupervisorRepository _inner;
  final OfflineGateway _gateway;
  final Uuid _uuid;

  OfflineSupervisorRepository({
    required SupervisorRepository inner,
    required OfflineGateway gateway,
    Uuid? uuid,
  })  : _inner = inner,
        _gateway = gateway,
        _uuid = uuid ?? const Uuid();

  @override
  Future<VisitResponse> storeVisit(VisitRequest request) async {
    final clientUuid = _uuid.v4();

    final body = await _gateway.write(
      endpoint: ApiConstants.supervisorStoreVisit,
      payload: {
        ...request.toJson(),
        'client_uuid': clientUuid,
      },
      // لكل زيارة معرّفها الخاص، فلا دمج — زيارتان لمعلّمين مختلفين في اليوم نفسه
      // حدثان مستقلان ولا يجوز أن يبتلع أحدهما الآخر.
      dedupeKey: 'supervisor:visit:$clientUuid',
      label: 'زيارة ميدانية بتاريخ ${request.visitDate}',
    );

    // الوصول إلى هنا يعني نجاح الإرسال الفوري (التأجيل يرمي QueuedOfflineException)،
    // ونبني النتيجة من جسم الاستجابة نفسه بدل إعادة إرسال الطلب.
    return VisitResponse.fromJson(body);
  }

  // --- قراءات تمرّ كما هي (الكاش مطبَّق في CachingApiClient) ---

  @override
  Future<Map<String, dynamic>> fetchMe() => _inner.fetchMe();

  @override
  Future<SupervisorStats> fetchStats() => _inner.fetchStats();

  @override
  Future<TeacherDetail> fetchTeacherDetail(int teacherId) => _inner.fetchTeacherDetail(teacherId);

  @override
  Future<HalaqahDaily> fetchHalaqahDaily(int halaqahId, {String? date}) =>
      _inner.fetchHalaqahDaily(halaqahId, date: date);

  @override
  Future<MyVisitsPageData> fetchMyVisits({int page = 1}) => _inner.fetchMyVisits(page: page);

  @override
  Future<AttendanceStats> fetchAttendanceStats({int days = 7}) =>
      _inner.fetchAttendanceStats(days: days);

  @override
  Future<List<Map<String, dynamic>>> fetchCenters() => _inner.fetchCenters();

  @override
  Future<List<Map<String, dynamic>>> fetchTeachers() => _inner.fetchTeachers();
}
