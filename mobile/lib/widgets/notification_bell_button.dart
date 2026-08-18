import 'package:flutter/material.dart';

import '../core/constants/api_constants.dart';
import '../features/notifications/presentation/notifications_page.dart';
import '../services/api/api_client.dart';
import '../storage/token_storage.dart';

/// أيقونة جرس في الـ AppBar مع شارة بعدد الإشعارات غير المقروءة.
///
/// Arabic: يستدعي `/notifications/unread-count` عند التهيئة.
/// EN: Bell icon with unread badge; fetches count from API on init.
class NotificationBellButton extends StatefulWidget {
  const NotificationBellButton({super.key, this.iconColor});

  final Color? iconColor;

  @override
  State<NotificationBellButton> createState() => _NotificationBellButtonState();
}

class _NotificationBellButtonState extends State<NotificationBellButton> {
  late final ApiClient _api;
  int? _count;

  @override
  void initState() {
    super.initState();
    _api = ApiClient(tokenStorage: SecureTokenStorage());
    _fetch();
  }

  Future<void> _fetch() async {
    try {
      final res = await _api.get(ApiConstants.notificationsUnreadCountEndpoint);
      final data = res.data;
      final c = data is Map ? data['count'] : null;
      if (!mounted) return;
      setState(() => _count = c is int ? c : int.tryParse('$c'));
    } catch (_) {
      // عدّاد الإشعارات ثانوي: أي فشل (شبكة أو جسم غير متوقع) يخفيه بصمت
      // بدل إسقاط الشاشة التي يظهر فيها.
      if (mounted) setState(() => _count = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          tooltip: 'الإشعارات',
          icon: Icon(Icons.notifications_outlined, color: widget.iconColor),
          onPressed: () async {
            await Navigator.push<void>(
              context,
              MaterialPageRoute<void>(builder: (_) => const NotificationsPage()),
            );
            await _fetch();
          },
        ),
        if ((_count ?? 0) > 0)
          Positioned(
            right: 6,
            top: 6,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              decoration: const BoxDecoration(
                color: Color(0xFFEF4444),
                shape: BoxShape.circle,
              ),
              constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
              child: Center(
                child: Text(
                  (_count! > 9) ? '9+' : '$_count',
                  style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
