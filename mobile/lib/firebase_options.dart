// وُلِّد من android/app/google-services.json لمشروع Firebase: halaqaty3
// لإعادة التوليد بعد أي تغيير في إعدادات Firebase: flutterfire configure

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// خيارات Firebase لكل منصة.
///
/// Arabic: أندرويد فقط مُهيّأ حالياً. المنصات الأخرى تُطلق خطأً صريحاً بدل
/// قيم وهمية تفشل بصمت وتُوهم أن الإشعارات تعمل.
/// EN: Android is configured; other platforms fail loudly instead of silently.
class DefaultFirebaseOptions {
  /// اختيار إعدادات المنصة الحالية.
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'لم تُهيّأ إعدادات Firebase للويب — شغّل flutterfire configure.',
      );
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      default:
        throw UnsupportedError(
          'لم تُهيّأ إعدادات Firebase لهذه المنصة ($defaultTargetPlatform) — '
          'شغّل flutterfire configure.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyB_w_EDJx_H9joBkVZNdfuwLv5e4VT4iE0',
    appId: '1:271743626110:android:b5672caac63860c1b6a12f',
    messagingSenderId: '271743626110',
    projectId: 'halaqaty3',
    storageBucket: 'halaqaty3.firebasestorage.app',
  );
}