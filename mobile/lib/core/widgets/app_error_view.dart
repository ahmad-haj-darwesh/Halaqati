import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// عرض خطأ بسيط مع زر «إعادة المحاولة» اختياري.
///
/// Arabic: يُستخدم في الشاشات التي تعتمد على الشبكة عند فشل التحميل.
/// EN: Centered error state with optional retry callback.
class AppErrorView extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;

  const AppErrorView({
    super.key,
    required this.message,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.cloud_off_rounded, size: 56, color: Colors.grey.shade500),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge,
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 20),
              FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('إعادة المحاولة'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// مؤشر تحميل مركزي مع نص اختياري.
///
/// Arabic: يستخدم لون `AppColors.forest` ليتماشى مع الثيم.
/// EN: Centered loading indicator with optional caption.
class AppLoadingView extends StatelessWidget {
  final String? message;

  const AppLoadingView({super.key, this.message});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const CircularProgressIndicator(color: AppColors.forest),
          if (message != null) ...[
            const SizedBox(height: 16),
            Text(
              message!,
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ],
        ],
      ),
    );
  }
}
