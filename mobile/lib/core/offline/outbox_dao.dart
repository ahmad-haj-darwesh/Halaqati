import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import 'offline_database.dart';
import 'offline_models.dart';

/// وصول البيانات لطابور الكتابة المؤجلة.
///
/// Arabic: يحافظ على ثابتة أساسية: لكل `dedupeKey` عنصر واحد بحالة `pending` كحد
/// أقصى، وعند تكرار الحفظ تُستبدل الحمولة مع **إبقاء** `client_recorded_at` الأصلي.
/// EN: Outbox persistence; coalesces repeated saves while pinning the original
/// client timestamp.
class OutboxDao {
  final OfflineDatabase _database;

  const OutboxDao(this._database);

  /// إضافة عملية للطابور، أو تحديث العملية المنتظرة المطابقة لنفس `dedupeKey`.
  ///
  /// Arabic: تُعيد المعرّف. المحاولات والأخطاء تُصفَّر عند التحديث لأن الحمولة
  /// الجديدة تستحق محاولة فورية جديدة.
  /// EN: Enqueues, or replaces an existing pending entry with the same dedupe key.
  Future<int> enqueue(OutboxEntry entry) async {
    final db = await _database.open();

    return db.transaction((txn) async {
      final dedupeKey = entry.dedupeKey;

      if (dedupeKey != null) {
        final existing = await txn.query(
          OfflineDatabase.outboxTable,
          columns: ['id', 'client_recorded_at'],
          where: 'user_key = ? AND dedupe_key = ? AND status = ?',
          whereArgs: [entry.userKey, dedupeKey, OutboxStatus.pending.name],
          limit: 1,
        );

        if (existing.isNotEmpty) {
          final id = existing.first['id'] as int;

          await txn.update(
            OfflineDatabase.outboxTable,
            {
              'payload': jsonEncode(entry.payload),
              'label': entry.label,
              'updated_at': entry.updatedAt.millisecondsSinceEpoch,
              'attempts': 0,
              'next_attempt_at': null,
              'last_error': null,
              // `client_recorded_at` غير مذكور عمداً: يبقى وقت أول إدخال.
            },
            where: 'id = ?',
            whereArgs: [id],
          );

          return id;
        }
      }

      return txn.insert(OfflineDatabase.outboxTable, _toRow(entry));
    });
  }

  /// العناصر الجاهزة للإرسال الآن، بترتيب الإدخال (FIFO).
  ///
  /// Arabic: الترتيب مهم — سجل يوم الأحد يجب أن يصل قبل تعديله يوم الاثنين.
  /// EN: Due entries in insertion order; ordering preserves causality.
  Future<List<OutboxEntry>> dueEntries(String userKey, DateTime now) async {
    final db = await _database.open();

    final rows = await db.query(
      OfflineDatabase.outboxTable,
      where: 'user_key = ? AND status = ? AND (next_attempt_at IS NULL OR next_attempt_at <= ?)',
      whereArgs: [userKey, OutboxStatus.pending.name, now.millisecondsSinceEpoch],
      orderBy: 'id ASC',
    );

    return rows.map(_fromRow).toList();
  }

  /// كل عناصر المستخدم (للعرض في شاشة المزامنة).
  Future<List<OutboxEntry>> all(String userKey) async {
    final db = await _database.open();

    final rows = await db.query(
      OfflineDatabase.outboxTable,
      where: 'user_key = ?',
      whereArgs: [userKey],
      orderBy: 'id ASC',
    );

    return rows.map(_fromRow).toList();
  }

  /// عدّ العناصر حسب الحالة.
  Future<int> countByStatus(String userKey, OutboxStatus status) async {
    final db = await _database.open();

    final result = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM ${OfflineDatabase.outboxTable} WHERE user_key = ? AND status = ?',
      [userKey, status.name],
    );

    return Sqflite.firstIntValue(result) ?? 0;
  }

  /// حذف عنصر بعد نجاح إرساله (أو بأمر المستخدم).
  Future<void> delete(int id) async {
    final db = await _database.open();
    await db.delete(OfflineDatabase.outboxTable, where: 'id = ?', whereArgs: [id]);
  }

  /// تسجيل فشل مؤقت وجدولة محاولة لاحقة.
  Future<void> markRetry(int id, int attempts, DateTime nextAttemptAt, String error) async {
    final db = await _database.open();

    await db.update(
      OfflineDatabase.outboxTable,
      {
        'attempts': attempts,
        'next_attempt_at': nextAttemptAt.millisecondsSinceEpoch,
        'last_error': error,
        'status': OutboxStatus.pending.name,
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  /// تسجيل رفض نهائي من الخادم — يحتاج تدخّل المستخدم.
  Future<void> markFailed(int id, int attempts, String error) async {
    final db = await _database.open();

    await db.update(
      OfflineDatabase.outboxTable,
      {
        'attempts': attempts,
        'next_attempt_at': null,
        'last_error': error,
        'status': OutboxStatus.failed.name,
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  /// إعادة عنصر فاشل إلى الانتظار بأمر المستخدم.
  ///
  /// Arabic: لو كان هناك عنصر منتظر بنفس `dedupeKey` لَما صحّ إحياء القديم فوقه —
  /// الفهرس الفريد يمنع ذلك، فنحذف الفاشل ونُبقي الأحدث.
  /// EN: User-triggered retry; drops the stale entry if a newer pending one exists.
  Future<void> retry(int id) async {
    final db = await _database.open();

    await db.transaction((txn) async {
      final rows = await txn.query(
        OfflineDatabase.outboxTable,
        where: 'id = ?',
        whereArgs: [id],
        limit: 1,
      );
      if (rows.isEmpty) return;

      final row = rows.first;
      final dedupeKey = row['dedupe_key'] as String?;

      if (dedupeKey != null) {
        final conflicting = await txn.query(
          OfflineDatabase.outboxTable,
          columns: ['id'],
          where: 'user_key = ? AND dedupe_key = ? AND status = ? AND id != ?',
          whereArgs: [row['user_key'], dedupeKey, OutboxStatus.pending.name, id],
          limit: 1,
        );

        if (conflicting.isNotEmpty) {
          await txn.delete(OfflineDatabase.outboxTable, where: 'id = ?', whereArgs: [id]);
          return;
        }
      }

      await txn.update(
        OfflineDatabase.outboxTable,
        {
          'status': OutboxStatus.pending.name,
          'attempts': 0,
          'next_attempt_at': null,
          'last_error': null,
        },
        where: 'id = ?',
        whereArgs: [id],
      );
    });
  }

  Map<String, Object?> _toRow(OutboxEntry entry) {
    return {
      'user_key': entry.userKey,
      'endpoint': entry.endpoint,
      'payload': jsonEncode(entry.payload),
      'dedupe_key': entry.dedupeKey,
      'label': entry.label,
      'client_recorded_at': entry.clientRecordedAt.millisecondsSinceEpoch,
      'updated_at': entry.updatedAt.millisecondsSinceEpoch,
      'attempts': entry.attempts,
      'next_attempt_at': entry.nextAttemptAt?.millisecondsSinceEpoch,
      'status': entry.status.name,
      'last_error': entry.lastError,
    };
  }

  OutboxEntry _fromRow(Map<String, Object?> row) {
    final nextAttempt = row['next_attempt_at'] as int?;

    return OutboxEntry(
      id: row['id'] as int,
      userKey: row['user_key'] as String,
      endpoint: row['endpoint'] as String,
      payload: jsonDecode(row['payload'] as String) as Map<String, dynamic>,
      dedupeKey: row['dedupe_key'] as String?,
      label: row['label'] as String,
      clientRecordedAt: DateTime.fromMillisecondsSinceEpoch(row['client_recorded_at'] as int),
      updatedAt: DateTime.fromMillisecondsSinceEpoch(row['updated_at'] as int),
      attempts: row['attempts'] as int,
      nextAttemptAt: nextAttempt == null ? null : DateTime.fromMillisecondsSinceEpoch(nextAttempt),
      status: OutboxStatus.values.firstWhere(
        (s) => s.name == row['status'],
        orElse: () => OutboxStatus.pending,
      ),
      lastError: row['last_error'] as String?,
    );
  }
}
