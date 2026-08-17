import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import 'offline_database.dart';
import 'offline_models.dart';

/// كاش استجابات القراءة.
///
/// Arabic: يخزّن آخر استجابة ناجحة لكل مسار كنص JSON خام. الاحتفاظ بالـ JSON بدل
/// النموذج المفكوك يعني أن تغيير حقول الـ DTO لاحقاً لا يكسر ما هو مخزّن — يُفكّ
/// بنفس `fromJson` المستخدم مع الشبكة.
/// EN: Read-response cache storing raw JSON so DTO changes stay backward compatible.
class CacheDao {
  final OfflineDatabase _database;

  const CacheDao(this._database);

  /// حفظ استجابة ناجحة.
  Future<void> put(String userKey, String cacheKey, Object payload, DateTime fetchedAt) async {
    final db = await _database.open();

    await db.insert(
      OfflineDatabase.cacheTable,
      {
        'user_key': userKey,
        'cache_key': cacheKey,
        'payload': jsonEncode(payload),
        'fetched_at': fetchedAt.millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// قراءة استجابة مخزّنة مع وقت جلبها، أو `null` إن لم توجد.
  Future<CachedPayload<Object?>?> get(String userKey, String cacheKey) async {
    final db = await _database.open();

    final rows = await db.query(
      OfflineDatabase.cacheTable,
      where: 'user_key = ? AND cache_key = ?',
      whereArgs: [userKey, cacheKey],
      limit: 1,
    );

    if (rows.isEmpty) return null;

    final row = rows.first;

    return CachedPayload(
      value: jsonDecode(row['payload'] as String),
      fetchedAt: DateTime.fromMillisecondsSinceEpoch(row['fetched_at'] as int),
    );
  }

  /// حذف مفتاح واحد.
  Future<void> remove(String userKey, String cacheKey) async {
    final db = await _database.open();

    await db.delete(
      OfflineDatabase.cacheTable,
      where: 'user_key = ? AND cache_key = ?',
      whereArgs: [userKey, cacheKey],
    );
  }

  /// مسح كاش مستخدم بالكامل.
  Future<void> clearForUser(String userKey) async {
    final db = await _database.open();

    await db.delete(
      OfflineDatabase.cacheTable,
      where: 'user_key = ?',
      whereArgs: [userKey],
    );
  }
}
