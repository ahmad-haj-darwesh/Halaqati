import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:halqati_mobile/storage/token_storage.dart';

import 'token_storage_test.mocks.dart';

@GenerateMocks([FlutterSecureStorage])
void main() {
  late MockFlutterSecureStorage mockStorage;
  late SecureTokenStorage tokenStorage;

  setUp(() {
    mockStorage = MockFlutterSecureStorage();
    tokenStorage = SecureTokenStorage(storage: mockStorage);
  });

  group('SecureTokenStorage', () {
    test('saveToken writes token to secure storage', () async {
      when(mockStorage.write(key: anyNamed('key'), value: anyNamed('value')))
          .thenAnswer((_) async {});

      await tokenStorage.saveToken('test-token-123');

      verify(mockStorage.write(key: 'sanctum_token', value: 'test-token-123'))
          .called(1);
    });

    test('getToken reads token from secure storage', () async {
      when(mockStorage.read(key: anyNamed('key')))
          .thenAnswer((_) async => 'test-token-123');

      final token = await tokenStorage.getToken();

      expect(token, equals('test-token-123'));
      verify(mockStorage.read(key: 'sanctum_token')).called(1);
    });

    test('getToken returns null when no token stored', () async {
      when(mockStorage.read(key: anyNamed('key')))
          .thenAnswer((_) async => null);

      final token = await tokenStorage.getToken();

      expect(token, isNull);
    });

    test('deleteToken deletes from secure storage', () async {
      when(mockStorage.delete(key: anyNamed('key')))
          .thenAnswer((_) async {});

      await tokenStorage.deleteToken();

      verify(mockStorage.delete(key: 'sanctum_token')).called(1);
    });

    test('hasToken returns true when token exists', () async {
      when(mockStorage.read(key: anyNamed('key')))
          .thenAnswer((_) async => 'some-token');

      final result = await tokenStorage.hasToken();

      expect(result, isTrue);
    });

    test('hasToken returns false when token is null', () async {
      when(mockStorage.read(key: anyNamed('key')))
          .thenAnswer((_) async => null);

      final result = await tokenStorage.hasToken();

      expect(result, isFalse);
    });

    test('hasToken returns false when token is empty string', () async {
      when(mockStorage.read(key: anyNamed('key')))
          .thenAnswer((_) async => '');

      final result = await tokenStorage.hasToken();

      expect(result, isFalse);
    });
  });
}
