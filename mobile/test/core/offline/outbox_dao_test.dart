import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/core/offline/offline_database.dart';
import 'package:halqati_mobile/core/offline/offline_models.dart';
import 'package:halqati_mobile/core/offline/outbox_dao.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

/// اختبارات طابور الكتابة على المخطط الحقيقي (قاعدة في الذاكرة).
///
/// Arabic: الثابتة الأهم هنا: الحفظ المتكرر لليوم نفسه يتّحد في عملية واحدة بأحدث
/// القيم، مع **إبقاء** `client_recorded_at` الأصلي — لأن الخادم يحسب نافذة تعديل
/// التقييم منه، فلو تقدّم مع كل حفظ لأمكن تمديد النافذة بلا حدود.
/// EN: Outbox tests against the real schema; coalescing must not advance the
/// pinned client timestamp.
void main() {
  sqfliteFfiInit();

  late OfflineDatabase database;
  late OutboxDao dao;

  const userKey = '7';
  final firstRecordedAt = DateTime(2026, 8, 12, 9, 0);

  OutboxEntry buildEntry({
    required Map<String, dynamic> payload,
    String? dedupeKey = 'teacher:daily:2026-08-12',
    DateTime? clientRecordedAt,
    String label = 'سجل اليوم',
  }) {
    return OutboxEntry(
      userKey: userKey,
      endpoint: '/teacher/daily-records/upsert',
      payload: payload,
      dedupeKey: dedupeKey,
      label: label,
      clientRecordedAt: clientRecordedAt ?? firstRecordedAt,
      updatedAt: DateTime(2026, 8, 12, 9, 0),
    );
  }

  setUp(() {
    database = OfflineDatabase(
      factory: databaseFactoryFfi,
      path: inMemoryDatabasePath,
    );
    dao = OutboxDao(database);
  });

  tearDown(() => database.close());

  test('يدرج عملية جديدة ويستردها بالترتيب', () async {
    await dao.enqueue(buildEntry(payload: {'date': '2026-08-12'}));

    final entries = await dao.all(userKey);

    expect(entries, hasLength(1));
    expect(entries.first.endpoint, '/teacher/daily-records/upsert');
    expect(entries.first.payload['date'], '2026-08-12');
    expect(entries.first.status, OutboxStatus.pending);
  });

  test('الحفظ المتكرر لنفس اليوم يتّحد في عملية واحدة بأحدث القيم', () async {
    await dao.enqueue(buildEntry(payload: {'date': '2026-08-12', 'records': 1}));
    await dao.enqueue(buildEntry(payload: {'date': '2026-08-12', 'records': 2}));
    await dao.enqueue(buildEntry(payload: {'date': '2026-08-12', 'records': 3}));

    final entries = await dao.all(userKey);

    expect(entries, hasLength(1));
    expect(entries.first.payload['records'], 3);
  });

  test('الدمج يُبقي وقت أول إدخال ولا يُقدّمه', () async {
    await dao.enqueue(buildEntry(payload: {'v': 1}));

    await dao.enqueue(buildEntry(
      payload: {'v': 2},
      clientRecordedAt: DateTime(2026, 8, 12, 11, 30),
    ));

    final entries = await dao.all(userKey);

    expect(entries.single.clientRecordedAt, firstRecordedAt);
  });

  test('العمليات بمفاتيح دمج مختلفة تبقى مستقلة', () async {
    await dao.enqueue(buildEntry(payload: {'d': 1}, dedupeKey: 'teacher:daily:2026-08-11'));
    await dao.enqueue(buildEntry(payload: {'d': 2}, dedupeKey: 'teacher:daily:2026-08-12'));

    expect(await dao.all(userKey), hasLength(2));
  });

  test('عملية فاشلة لا تبتلعها محاولة حفظ جديدة لنفس اليوم', () async {
    final id = await dao.enqueue(buildEntry(payload: {'v': 1}));
    await dao.markFailed(id, 1, 'تاريخ خارج النافذة');

    await dao.enqueue(buildEntry(payload: {'v': 2}));

    final entries = await dao.all(userKey);

    expect(entries, hasLength(2), reason: 'الفاشلة يجب أن تبقى ظاهرة للمستخدم');
    expect(entries.where((e) => e.status == OutboxStatus.failed), hasLength(1));
    expect(entries.where((e) => e.status == OutboxStatus.pending), hasLength(1));
  });

  test('العناصر المؤجَّلة لا تظهر ضمن المستحقة قبل موعدها', () async {
    final id = await dao.enqueue(buildEntry(payload: {'v': 1}));
    final now = DateTime(2026, 8, 12, 12, 0);

    await dao.markRetry(id, 1, now.add(const Duration(minutes: 10)), 'تعذّر الاتصال');

    expect(await dao.dueEntries(userKey, now), isEmpty);
    expect(await dao.dueEntries(userKey, now.add(const Duration(minutes: 11))), hasLength(1));
  });

  test('العناصر الفاشلة لا تُرسل تلقائياً', () async {
    final id = await dao.enqueue(buildEntry(payload: {'v': 1}));
    await dao.markFailed(id, 3, 'مرفوض');

    expect(await dao.dueEntries(userKey, DateTime(2026, 8, 12, 23, 0)), isEmpty);
  });

  test('إعادة المحاولة تُعيد العنصر الفاشل للانتظار وتصفّر عدّاده', () async {
    final id = await dao.enqueue(buildEntry(payload: {'v': 1}));
    await dao.markFailed(id, 4, 'مرفوض');

    await dao.retry(id);

    final entry = (await dao.all(userKey)).single;
    expect(entry.status, OutboxStatus.pending);
    expect(entry.attempts, 0);
    expect(entry.lastError, isNull);
  });

  test('إعادة محاولة عنصر فاشل يُسقطه إن وُجد أحدث منه ينتظر بنفس المفتاح', () async {
    final staleId = await dao.enqueue(buildEntry(payload: {'v': 1}));
    await dao.markFailed(staleId, 2, 'مرفوض');
    await dao.enqueue(buildEntry(payload: {'v': 2}));

    await dao.retry(staleId);

    final entries = await dao.all(userKey);
    expect(entries, hasLength(1));
    expect(entries.single.payload['v'], 2, reason: 'يبقى الأحدث لا القديم');
  });

  test('العدّادات تفصل المنتظر عن الفاشل', () async {
    final failedId = await dao.enqueue(buildEntry(payload: {'v': 1}, dedupeKey: 'a'));
    await dao.markFailed(failedId, 1, 'مرفوض');
    await dao.enqueue(buildEntry(payload: {'v': 2}, dedupeKey: 'b'));

    expect(await dao.countByStatus(userKey, OutboxStatus.pending), 1);
    expect(await dao.countByStatus(userKey, OutboxStatus.failed), 1);
  });

  test('طابور مستخدم لا يظهر لمستخدم آخر على الجهاز نفسه', () async {
    await dao.enqueue(buildEntry(payload: {'v': 1}));

    expect(await dao.all('99'), isEmpty);
    expect(await dao.countByStatus('99', OutboxStatus.pending), 0);
  });

  test('الحذف يزيل العنصر نهائياً', () async {
    final id = await dao.enqueue(buildEntry(payload: {'v': 1}));

    await dao.delete(id);

    expect(await dao.all(userKey), isEmpty);
  });
}
