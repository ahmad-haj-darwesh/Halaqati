import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// قاعدة البيانات المحلية للعمل دون إنترنت (SQLite).
///
/// Arabic: تحتوي جدولين فقط: `outbox` لطابور الكتابة المؤجلة، و`cache_entries` لآخر
/// استجابة قراءة ناجحة لكل مسار. الجدولان مفصولان عمداً لأن للطابور دورة حياة
/// (محاولات، أخطاء، حذف بعد النجاح) بينما الكاش مجرد لقطة قابلة للاستبدال.
/// EN: Local SQLite store: a write outbox and a read cache, kept separate because
/// only the outbox has a retry lifecycle.
class OfflineDatabase {
  static const String outboxTable = 'outbox';
  static const String cacheTable = 'cache_entries';
  static const int _schemaVersion = 1;

  /// مصنع قاعدة البيانات ومسارها.
  ///
  /// Arabic: قابلان للحقن ليتمكن الاختبار من تشغيل المخطط نفسه على قاعدة في الذاكرة
  /// عبر `sqflite_common_ffi` — فما يُختبر هو المخطط الفعلي لا نسخة مبسّطة منه.
  /// EN: Injectable so tests exercise the real schema against an in-memory database.
  final DatabaseFactory? _factoryOverride;
  final String? _pathOverride;

  OfflineDatabase({DatabaseFactory? factory, String? path})
      : _factoryOverride = factory,
        _pathOverride = path;

  Database? _db;

  /// فتح القاعدة (أو إعادة النسخة المفتوحة).
  ///
  /// Arabic: sqflite لا يدعم الويب، لذا يُتوقّع من المستدعي التحقق من [isSupported]
  /// قبل الاستخدام والعودة لوضع «متصل فقط» على الويب.
  /// EN: Opens (or reuses) the database. Callers must check [isSupported] first.
  Future<Database> open() async {
    final existing = _db;
    if (existing != null) return existing;

    final factory = _factoryOverride ?? databaseFactory;
    final path = _pathOverride ?? p.join(await factory.getDatabasesPath(), 'halqati_offline.db');

    final db = await factory.openDatabase(
      path,
      options: OpenDatabaseOptions(
        version: _schemaVersion,
        onConfigure: (db) => db.execute('PRAGMA foreign_keys = ON'),
        onCreate: _createSchema,
      ),
    );

    _db = db;
    return db;
  }

  /// هل المنصة الحالية تدعم التخزين المحلي؟
  ///
  /// Arabic: على الويب يبقى التطبيق يعمل لكن دون طابور ولا كاش — أي بسلوكه السابق.
  /// EN: Web has no sqflite backend; the app stays online-only there.
  static bool get isSupported => !kIsWeb;

  Future<void> _createSchema(Database db, int version) async {
    await db.execute('''
      CREATE TABLE $outboxTable (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_key TEXT NOT NULL,
        endpoint TEXT NOT NULL,
        payload TEXT NOT NULL,
        dedupe_key TEXT,
        label TEXT NOT NULL,
        client_recorded_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0,
        next_attempt_at INTEGER,
        status TEXT NOT NULL DEFAULT 'pending',
        last_error TEXT
      )
    ''');

    // الدمج مقصور على العناصر التي ما زالت تنتظر: عنصر فشل نهائياً يجب أن يبقى
    // ظاهراً للمستخدم بدل أن تبتلعه محاولة حفظ جديدة لنفس اليوم.
    await db.execute('''
      CREATE UNIQUE INDEX idx_outbox_dedupe
      ON $outboxTable (user_key, dedupe_key)
      WHERE dedupe_key IS NOT NULL AND status = 'pending'
    ''');

    await db.execute(
      'CREATE INDEX idx_outbox_queue ON $outboxTable (user_key, status, id)',
    );

    await db.execute('''
      CREATE TABLE $cacheTable (
        user_key TEXT NOT NULL,
        cache_key TEXT NOT NULL,
        payload TEXT NOT NULL,
        fetched_at INTEGER NOT NULL,
        PRIMARY KEY (user_key, cache_key)
      )
    ''');
  }

  /// إغلاق القاعدة (يُستخدم في الاختبارات وعند تسجيل الخروج الكامل).
  Future<void> close() async {
    await _db?.close();
    _db = null;
  }
}
