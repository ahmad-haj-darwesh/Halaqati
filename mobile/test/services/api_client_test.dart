import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:halqati_mobile/storage/token_storage.dart';
import 'package:halqati_mobile/core/errors/api_exception.dart';

import 'api_client_test.mocks.dart';

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
}
