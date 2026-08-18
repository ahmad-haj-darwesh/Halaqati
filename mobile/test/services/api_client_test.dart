import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:halqati_mobile/storage/token_storage.dart';
import 'package:halqati_mobile/core/errors/api_exception.dart';
import 'package:halqati_mobile/services/api/api_client.dart';

import 'api_client_test.mocks.dart';

/// محوّل HTTP وهمي يعيد جسماً وحالة محددين دون أي اتصال بالشبكة.
class _FakeAdapter implements HttpClientAdapter {
  final String body;
  final int statusCode;
  final String contentType;

  _FakeAdapter(this.body, this.statusCode, this.contentType);

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    return ResponseBody.fromString(
      body,
      statusCode,
      headers: {
        Headers.contentTypeHeader: [contentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

@GenerateMocks([TokenStorage, Dio])
void main() {
  late MockTokenStorage mockTokenStorage;
  late MockDio mockDio;

  setUp(() {
    mockTokenStorage = MockTokenStorage();
    mockDio = MockDio();

    when(mockDio.options).thenReturn(BaseOptions());
    when(mockDio.interceptors).thenReturn(Interceptors());
  });

  group('AuthRepository.login parsing', () {
    test('LoginResult parses correctly from JSON', () {
      final json = {
        'token': 'abc123',
        'user': {'id': 1, 'name': 'Ahmed', 'email': 'ahmed@test.com'},
      };

      expect(json['token'], equals('abc123'));
      expect((json['user'] as Map)['name'], equals('Ahmed'));
      expect((json['user'] as Map)['id'], equals(1));
    });
  });

  group('ApiException', () {
    test('ApiException stores message and statusCode', () {
      const e = ApiException(message: 'خطأ في الاتصال', statusCode: 422);
      expect(e.message, equals('خطأ في الاتصال'));
      expect(e.statusCode, equals(422));
    });

    test('ApiException toString includes code and message', () {
      const e = ApiException(message: 'Not found', statusCode: 404);
      expect(e.toString(), contains('404'));
      expect(e.toString(), contains('Not found'));
    });
  });

  group('TokenStorage mock', () {
    test('saveToken is called with correct value', () async {
      when(mockTokenStorage.saveToken(any)).thenAnswer((_) async {});
      await mockTokenStorage.saveToken('my-token');
      verify(mockTokenStorage.saveToken('my-token')).called(1);
    });

    test('getToken returns stored token', () async {
      when(mockTokenStorage.getToken()).thenAnswer((_) async => 'saved-token');
      final token = await mockTokenStorage.getToken();
      expect(token, equals('saved-token'));
    });

    test('deleteToken clears storage', () async {
      when(mockTokenStorage.deleteToken()).thenAnswer((_) async {});
      await mockTokenStorage.deleteToken();
      verify(mockTokenStorage.deleteToken()).called(1);
    });

    test('hasToken returns true when token available', () async {
      when(mockTokenStorage.hasToken()).thenAnswer((_) async => true);
      final result = await mockTokenStorage.hasToken();
      expect(result, isTrue);
    });

    test('hasToken returns false when no token', () async {
      when(mockTokenStorage.hasToken()).thenAnswer((_) async => false);
      final result = await mockTokenStorage.hasToken();
      expect(result, isFalse);
    });
  });

  group('ApiClient._handleError — أجسام استجابة غير متوقعة', () {
    ApiClient clientReturning(String body, int status, String contentType) {
      when(mockTokenStorage.getToken()).thenAnswer((_) async => null);
      final dio = Dio()..httpClientAdapter = _FakeAdapter(body, status, contentType);
      return ApiClient(dio: dio, tokenStorage: mockTokenStorage);
    }

    Future<ApiException> captureError(ApiClient client) async {
      try {
        await client.post('/login', data: {'email': 'a@b.c'});
      } on ApiException catch (e) {
        return e;
      }
      fail('توقّعنا ApiException ولم يُطلق أي استثناء');
    }

    // الانحدار الأصلي: جسم HTML كان يُفهرس كخريطة فيُطلق
    // "type 'String' is not a subtype of type 'int' of 'index'".
    test('جسم HTML لا يُطلق TypeError بل ApiException برسالة عربية', () async {
      final client = clientReturning(
        '<!DOCTYPE html><html><body>Service Unavailable</body></html>',
        503,
        'text/html',
      );

      final error = await captureError(client);

      expect(error.statusCode, 503);
      expect(error.message, contains('الخادم'));
      expect(error.message, isNot(contains('subtype')));
    });

    test('جسم نصي فارغ لا يُسقط المعالجة', () async {
      final client = clientReturning('', 502, 'text/plain');
      final error = await captureError(client);

      expect(error.statusCode, 502);
      expect(error.message, isNotEmpty);
    });

    test('أخطاء التحقق تُعرض أول رسالة من الخادم', () async {
      final client = clientReturning(
        '{"message":"فشل التحقق","errors":{"email":["بيانات الاعتماد غير صحيحة."]}}',
        422,
        'application/json',
      );

      final error = await captureError(client);

      expect(error.message, 'بيانات الاعتماد غير صحيحة.');
    });

    test('errors فارغة ترجع إلى رسالة الخادم بدل الانهيار', () async {
      final client = clientReturning(
        '{"message":"طلب غير صالح","errors":{}}',
        422,
        'application/json',
      );

      final error = await captureError(client);

      expect(error.message, 'طلب غير صالح');
    });

    test('قائمة أخطاء فارغة ترجع إلى رسالة الخادم بدل الانهيار', () async {
      final client = clientReturning(
        '{"message":"طلب غير صالح","errors":{"email":[]}}',
        422,
        'application/json',
      );

      final error = await captureError(client);

      expect(error.message, 'طلب غير صالح');
    });

    test('401 يعطي رسالة انتهاء الجلسة', () async {
      final client = clientReturning('{}', 401, 'application/json');
      final error = await captureError(client);

      expect(error.statusCode, 401);
      expect(error.message, contains('الجلسة'));
    });

    // 403 بصفحة HTML يعني أن وسيطاً في الشبكة حجب الطلب قبل وصوله إلى الـ API،
    // فرسالة الصلاحيات تُوجّه المستخدم إلى تشخيص خاطئ تماماً.
    test('403 بجسم HTML يُنسب للشبكة لا لصلاحيات المستخدم', () async {
      final client = clientReturning(
        '<html><body>403 Forbidden — Access to this resource on the server is denied!</body></html>',
        403,
        'text/html',
      );

      final error = await captureError(client);

      expect(error.statusCode, 403);
      expect(error.message, isNot(contains('صلاحية')));
      expect(error.message, contains('حُجب'));
    });

    test('403 بجسم JSON بلا رسالة يُنسب للصلاحيات', () async {
      final client = clientReturning('{}', 403, 'application/json');
      final error = await captureError(client);

      expect(error.message, contains('صلاحية'));
    });

    test('403 بجسم JSON مع رسالة يعرض رسالة الخادم نفسها', () async {
      final client = clientReturning(
        '{"message":"هذا الحساب غير مصرّح له باستخدام التطبيق."}',
        403,
        'application/json',
      );

      final error = await captureError(client);

      expect(error.message, 'هذا الحساب غير مصرّح له باستخدام التطبيق.');
    });
  });

  group('friendlyErrorMessage', () {
    test('يمرّر رسالة ApiException كما هي', () {
      const e = ApiException(message: 'تعذّر الاتصال بالخادم.', statusCode: null);
      expect(friendlyErrorMessage(e), 'تعذّر الاتصال بالخادم.');
    });

    test('يحجب تفاصيل الأخطاء التقنية عن المستخدم', () {
      final e = TypeError();
      final message = friendlyErrorMessage(e);

      expect(message, isNot(contains('subtype')));
      expect(message, contains('حاول مرة أخرى'));
    });
  });
}
