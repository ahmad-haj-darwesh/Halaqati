import 'package:dio/dio.dart';

import '../../services/api/api_client.dart';
import '../../storage/token_storage.dart';
import '../errors/api_exception.dart';
import 'cache_dao.dart';
import 'offline_database.dart';

/// عميل API يخزّن استجابات القراءة ويعيدها عند انقطاع الشبكة.
///
/// Arabic: وُضِع الكاش هنا لا داخل كل مستودع عن قصد — المستودعات تُعيد نماذج (DTOs)
/// بينما الكاش يحتاج JSON الخام، فتغليفها فرداً فرداً كان سيفرض كتابة `toJson` لكل
/// نموذج. باعتراض `get` في مستوى واحد تصبح **كل** شاشات القراءة (المعلم، المشرف،
/// المختبر) تعمل دون إنترنت بلا سطر إضافي في أي مستودع.
/// EN: Read-through cache at the HTTP layer, so every GET-backed screen works
/// offline without per-repository serialization code.
class CachingApiClient extends ApiClient {
  final CacheDao _cache;
  final TokenStorage _tokenStorage;

  CachingApiClient({
    super.dio,
    required super.tokenStorage,
    required CacheDao cache,
  })  : _cache = cache,
        _tokenStorage = tokenStorage;

  @override
  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    bool forceRefresh = false,
  }) async {
    final userKey = await _tokenStorage.getUserKey();
    final canCache = OfflineDatabase.isSupported && userKey != null;
    final key = cacheKeyFor(path, queryParameters);

    try {
      final response = await super.get(
        path,
        queryParameters: queryParameters,
        forceRefresh: forceRefresh,
      );

      final data = response.data;
      if (canCache && data != null) {
        await _cache.put(userKey, key, data, DateTime.now());
      }

      return response;
    } on ApiException catch (e) {
      // رفض الخادم (403/422) جواب صحيح يجب أن يصل المستخدم؛ لا يُخفى خلف نسخة قديمة.
      // أما غياب `statusCode` فيعني أن الطلب لم يصل أصلاً — هنا فقط ينفع الكاش.
      if (!canCache || e.statusCode != null) rethrow;

      final cached = await _cache.get(userKey, key);
      if (cached == null || cached.value == null) rethrow;

      return Response(
        requestOptions: RequestOptions(path: path, queryParameters: queryParameters),
        data: cached.value,
        statusCode: 200,
        extra: {
          fromCacheFlag: true,
          cachedAtFlag: cached.fetchedAt.toIso8601String(),
        },
      );
    }
  }

  /// مفتاح الكاش: المسار مع معاملاته مرتّبة ليكون ثابتاً بغض النظر عن ترتيب الإدخال.
  /// EN: Stable cache key from path + sorted query params.
  static String cacheKeyFor(String path, Map<String, dynamic>? queryParameters) {
    // بعض المستودعات تمرّر المعاملات داخل المسار نفسه (`...?date=x`) لا كخريطة.
    if (queryParameters == null || queryParameters.isEmpty) return 'GET $path';

    final keys = queryParameters.keys.toList()..sort();
    final query = keys.map((k) => '$k=${queryParameters[k]}').join('&');

    return 'GET $path?$query';
  }

  /// علامة في `Response.extra` تدل على أن البيانات جاءت من الكاش.
  static const String fromCacheFlag = 'halqati_from_cache';

  /// علامة في `Response.extra` تحمل وقت تخزين النسخة.
  static const String cachedAtFlag = 'halqati_cached_at';

  /// هل هذه الاستجابة من الكاش؟
  static bool isFromCache(Response response) => response.extra[fromCacheFlag] == true;

  /// وقت تخزين الاستجابة إن كانت من الكاش.
  static DateTime? cachedAt(Response response) {
    final raw = response.extra[cachedAtFlag];
    return raw is String ? DateTime.tryParse(raw) : null;
  }
}
