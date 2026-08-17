import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'app/role_home.dart';
import 'core/firebase/fcm_setup.dart';
import 'core/offline/sync_manager.dart';
import 'core/offline/widgets/sync_status_bar.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/data/auth_repository.dart';
import 'features/auth/presentation/login_page.dart';
import 'firebase_options.dart';
import 'injection_container.dart'; // 👈 استيراد ملف الاعتماديات
import 'storage/token_storage.dart';

/// نقطة دخول تطبيق "حلقتي".
///
/// Arabic: تهيّئ Firebase (عند توفره) وتسجّل مستمعي FCM، تهيئ الاعتماديات، ثم تشغّل تطبيق Flutter.
/// EN: App entrypoint that initializes Firebase/FCM, DI, and boots the Flutter app.
Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // 👈 استدعاء الدالة لتشغيل نظام حقن الاعتماديات فوراً
  initDependencies();

  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  } catch (e, st) {
    debugPrint('Firebase init skipped: $e\n$st');
  }
  
  // 👈 استخدام الاعتماد المحقون بدلاً من SecureTokenStorage()
  final storage = sl<TokenStorage>();
  await registerFcmTokenListeners(storage);

  // بدء مراقبة الشبكة وتفريغ ما تبقّى في الطابور من جلسة سابقة (مثلاً إن أُغلق
  // التطبيق قبل عودة الإنترنت). لا يُعطّل الإقلاع لو فشل فتح قاعدة البيانات المحلية.
  try {
    await sl<SyncManager>().start();
  } catch (e, st) {
    debugPrint('Offline sync start failed: $e\n$st');
  }

  runApp(const HalqatiApp());
}

class HalqatiApp extends StatelessWidget {
  const HalqatiApp({super.key});

  /// بناء التطبيق الأساسي مع تفعيل RTL واللغة العربية.
  ///
  /// Arabic: يعتمد `buildAppTheme()` لتوحيد التصميم ويجعل اتجاه النص RTL بشكل صريح.
  /// EN: Builds the MaterialApp with Arabic locale and RTL directionality.
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'حلقتي',
      debugShowCheckedModeBanner: false,
      theme: buildAppTheme(),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) {
        // الشريط يُركَّب هنا مرة واحدة ليظهر فوق كل الشاشات دون تعديل أي منها.
        return Directionality(
          textDirection: TextDirection.rtl,
          child: Column(
            children: [
              const SyncStatusBar(),
              Expanded(child: child ?? const SizedBox.shrink()),
            ],
          ),
        );
      },
      home: const _Splash(),
    );
  }
}

/// شاشة بدء مع شعار التطبيق؛ تتحقق من التوكن وتوجّه إلى الدخول أو [RoleHome].
/// EN: Splash screen that resolves auth state before entering the main route.
class _Splash extends StatefulWidget {
  const _Splash();

  @override
  State<_Splash> createState() => _SplashState();
}

class _SplashState extends State<_Splash> {
  @override
  void initState() {
    super.initState();
    _checkToken();
  }

  /// تحديد الوجهة الأولى بناءً على وجود التوكن ودور المستخدم.
  ///
  /// Arabic: إن لم يوجد توكن يتم الذهاب لصفحة الدخول. إن وُجد، يحاول قراءة الدور
  /// من التخزين المحلي ثم يجلبه من الخادم عند الحاجة.
  /// EN: Determines initial route based on stored token and user role.
  Future<void> _checkToken() async {
    // 👈 استخدام الاعتماد المحقون
    final storage = sl<TokenStorage>();
    final hasToken = await storage.hasToken();
    if (!mounted) return;

    if (!hasToken) {
      _goLogin();
      return;
    }

    var role = await storage.getRole();
    if (role == null || role.isEmpty) {
      try {
        // 👈 استدعاء مستودع المصادقة عبر GetIt بدلاً من بنائه يدوياً
        final auth = sl<AuthRepository>();
        final session = await auth.fetchSession();
        role = session?.role;
      } catch (_) {
        await storage.deleteToken();
        if (!mounted) return;
        _goLogin();
        return;
      }
    }

    if (!mounted) return;
    if (role == null || role.isEmpty) {
      _goLogin();
      return;
    }

    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        pageBuilder: (_, __, ___) => RoleHome(role: role!),
        transitionsBuilder: (_, animation, __, child) {
          return FadeTransition(opacity: animation, child: child);
        },
        transitionDuration: const Duration(milliseconds: 320),
      ),
    );
  }

  void _goLogin() {
    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        pageBuilder: (_, __, ___) => const LoginPage(),
        transitionsBuilder: (_, animation, __, child) {
          return FadeTransition(opacity: animation, child: child);
        },
        transitionDuration: const Duration(milliseconds: 320),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFF0D3D2E),
              Color(0xFF1B5E4A),
              Color(0xFF2A7D6A),
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.menu_book_rounded,
                  size: 64,
                  color: Color(0xFFE8D48A),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'حلقتي',
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.5,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                'إدارة الحلقة القرآنية',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85),
                  fontSize: 15,
                ),
              ),
              const SizedBox(height: 48),
              const SizedBox(
                width: 36,
                height: 36,
                child: CircularProgressIndicator(
                  strokeWidth: 3,
                  color: Color(0xFFE8D48A),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}