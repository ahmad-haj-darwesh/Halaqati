import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../../firebase_options.dart';
import '../../storage/token_storage.dart';

/// إعدادات Firebase Cloud Messaging (FCM) لتطبيق الموبايل.
///
/// Arabic: يحتوي على معالج رسائل الخلفية، بالإضافة إلى تهيئة طلب الإذن وتخزين
/// التوكن والاستماع لتحديثاته.
/// EN: FCM setup for background/foreground handling and token persistence.

/// يجب أن تكون دالة عالمية لخلفية FCM.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  debugPrint('FCM background: ${message.messageId}');
}

/// طلب الإذن، جلب التوكن، والاستماع للتحديثات (بعد [Firebase.initializeApp] و
/// [FirebaseMessaging.onBackgroundMessage] في [main]).
///
/// Arabic: يحفظ التوكن محلياً لاستخدامه لاحقاً من الباكند لإرسال الإشعارات.
/// EN: Requests permissions, stores the token, and listens for token refresh events.
// 👈 التعديل هنا: استخدام TokenStorage بدلاً من SecureTokenStorage
Future<void> registerFcmTokenListeners(TokenStorage storage) async { 
  try {
    final messaging = FirebaseMessaging.instance;
    await messaging.setAutoInitEnabled(true);

    await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    final token = await messaging.getToken();
    if (token != null && token.isNotEmpty) {
      await storage.saveFcmToken(token);
    }

    messaging.onTokenRefresh.listen((newToken) async {
      await storage.saveFcmToken(newToken);
    });

    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint('FCM foreground: ${message.notification?.title}');
    });
  } catch (e, st) {
    debugPrint('registerFcmTokenListeners: $e\n$st');
  }
}