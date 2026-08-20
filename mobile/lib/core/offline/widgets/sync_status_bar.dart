import 'package:flutter/foundation.dart';
import '../../../core/utils/arabic_plural.dart';
import 'package:flutter/material.dart';

import '../../../injection_container.dart';
import '../offline_models.dart';
import '../sync_manager.dart';
import 'sync_queue_page.dart';

/// شريط يظهر أعلى الشاشة عند انقطاع الشبكة أو وجود عمل غير مُرسل.
///
/// Arabic: يختفي تماماً في الحالة الطبيعية (متصل ولا شيء معلّق) حتى لا يزاحم المحتوى.
/// الغرض منه طمأنة المعلّم بأن ما سجّله محفوظ، لا تنبيهه بخطأ.
/// EN: Slim status strip; hidden entirely when online with an empty queue.
class SyncStatusBar extends StatelessWidget {
  /// مصدر الحالة؛ يُقرأ من حاوية الاعتماديات حين لا يُمرَّر (ويُتجاوز في الاختبارات).
  final ValueListenable<SyncSnapshot>? listenable;

  const SyncStatusBar({super.key, this.listenable});

  @override
  Widget build(BuildContext context) {
    // شريط زخرفي يُركَّب فوق كل الشاشات، فلا يجوز أن يُسقط التطبيق لو لم تُهيَّأ
    // طبقة المزامنة بعد (اختبار ويدجت، أو إقلاع فشل فيه فتح القاعدة المحلية).
    final source = listenable ??
        (sl.isRegistered<SyncManager>() ? sl<SyncManager>().snapshot : null);

    if (source == null) return const SizedBox.shrink();

    return ValueListenableBuilder<SyncSnapshot>(
      valueListenable: source,
      builder: (context, snapshot, _) {
        if (snapshot.isOnline && !snapshot.hasUnsyncedWork) {
          return const SizedBox.shrink();
        }

        final theme = _themeFor(snapshot);

        return Material(
          color: theme.background,
          child: InkWell(
            onTap: snapshot.hasUnsyncedWork
                ? () => Navigator.of(context).push(
                      MaterialPageRoute<void>(builder: (_) => const SyncQueuePage()),
                    )
                : null,
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                child: Row(
                  children: [
                    if (snapshot.isSyncing)
                      const SizedBox(
                        width: 14,
                        height: 14,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    else
                      Icon(theme.icon, size: 18, color: Colors.white),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        theme.message,
                        style: const TextStyle(color: Colors.white, fontSize: 13),
                      ),
                    ),
                    if (snapshot.hasUnsyncedWork)
                      const Icon(Icons.chevron_left, size: 18, color: Colors.white),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  _BarTheme _themeFor(SyncSnapshot s) {
    if (s.failedCount > 0) {
      return _BarTheme(
        background: const Color(0xFFB3261E),
        icon: Icons.error_outline,
        message: '${ArCount.operations(s.failedCount)} لم تُقبل — اضغط للمراجعة',
      );
    }

    if (s.isSyncing) {
      return const _BarTheme(
        background: Color(0xFF2A7D6A),
        icon: Icons.sync,
        message: 'جارٍ المزامنة…',
      );
    }

    if (!s.isOnline) {
      return _BarTheme(
        background: const Color(0xFF6B5B2E),
        icon: Icons.cloud_off,
        message: s.pendingCount > 0
            ? 'وضع دون إنترنت — ${ArCount.operations(s.pendingCount)} محفوظة ستُرسل تلقائياً'
            : 'وضع دون إنترنت — يمكنك متابعة العمل والتسجيل',
      );
    }

    return _BarTheme(
      background: const Color(0xFF1B5E4A),
      icon: Icons.cloud_upload_outlined,
      message: '${ArCount.operations(s.pendingCount)} بانتظار الإرسال',
    );
  }
}

class _BarTheme {
  final Color background;
  final IconData icon;
  final String message;

  const _BarTheme({
    required this.background,
    required this.icon,
    required this.message,
  });
}
