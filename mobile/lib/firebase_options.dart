// استبدل هذا الملف بتشغيل: dart pub global activate flutterfire_cli && flutterfire configure
// حتى يعمل Firebase Cloud Messaging فعلياً.

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// خيارات Firebase لكل منصة (يُستبدل عادةً بمخرجات FlutterFire CLI).
///
/// Arabic: القيم الحالية placeholders؛ شغّل `flutterfire configure` لملء المفاتيح الحقيقية.
/// EN: Per-platform FirebaseOptions; replace via FlutterFire when enabling FCM.
class DefaultFirebaseOptions {
  /// اختيار إعدادات المنصة الحالية (ويب / أندرويد / iOS).
  /// EN: Resolves FirebaseOptions for the running platform.
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        return web;
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'REPLACE_ME',
    appId: '1:000000000000:web:0000000000000000000000',
    messagingSenderId: '000000000000',
    projectId: 'halqati-placeholder',
    authDomain: 'halqati-placeholder.firebaseapp.com',
    storageBucket: 'halqati-placeholder.appspot.com',
  );

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'REPLACE_ME',
    appId: '1:000000000000:android:0000000000000000000000',
    messagingSenderId: '000000000000',
    projectId: 'halqati-placeholder',
    storageBucket: 'halqati-placeholder.appspot.com',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'REPLACE_ME',
    appId: '1:000000000000:ios:0000000000000000000000',
    messagingSenderId: '000000000000',
    projectId: 'halqati-placeholder',
    storageBucket: 'halqati-placeholder.appspot.com',
    iosBundleId: 'com.example.halqatiMobile',
  );
}
