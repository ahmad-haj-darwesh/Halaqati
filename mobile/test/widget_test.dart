import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/injection_container.dart';
import 'package:halqati_mobile/main.dart';
import 'package:halqati_mobile/storage/token_storage.dart';

/// اختبار دخاني لبناء التطبيق.
///
/// Arabic: شاشة البدء تقرأ `TokenStorage` من حاوية الاعتماديات فور `initState`، لذا
/// لا بد من تسجيل بديل قبل البناء. نستخدم تخزيناً في الذاكرة بلا توكن — فلا يلمس
/// الاختبار قنوات المنصة (Secure Storage) ويسلك مسار «مستخدم غير مسجَّل».
/// EN: Registers an in-memory token storage so the splash can resolve it without
/// touching platform channels.
class _FakeTokenStorage implements TokenStorage {
  String? _token;
  String? _role;
  String? _fcm;
  String? _userKey;

  @override
  Future<void> saveToken(String token) async => _token = token;

  @override
  Future<String?> getToken() async => _token;

  @override
  Future<void> deleteToken() async {
    _token = null;
    _role = null;
    _fcm = null;
    _userKey = null;
  }

  @override
  Future<bool> hasToken() async => _token != null && _token!.isNotEmpty;

  @override
  Future<void> saveRole(String role) async => _role = role;

  @override
  Future<String?> getRole() async => _role;

  @override
  Future<void> saveFcmToken(String token) async => _fcm = token;

  @override
  Future<String?> getFcmToken() async => _fcm;

  @override
  Future<void> deleteFcmToken() async => _fcm = null;

  @override
  Future<void> saveUserKey(String userKey) async => _userKey = userKey;

  @override
  Future<String?> getUserKey() async => _userKey;
}

void main() {
  setUp(() async {
    await sl.reset();
    // الصفحات صارت تحصل على المستودعات من الحاوية (لتعمل طبقة الكاش والطابور)،
    // فلا بد من تهيئتها كما يفعل main، ثم استبدال التخزين الآمن بتخزين في الذاكرة
    // حتى لا يلمس الاختبار قنوات المنصة.
    initDependencies();
    await sl.unregister<TokenStorage>();
    sl.registerLazySingleton<TokenStorage>(_FakeTokenStorage.new);
  });

  tearDown(() => sl.reset());

  testWidgets('App builds smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const HalqatiApp());
    await tester.pump();

    expect(find.byType(HalqatiApp), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
