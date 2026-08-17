import 'package:flutter/material.dart';

import '../../../injection_container.dart';
import '../offline_models.dart';
import '../sync_manager.dart';

/// شاشة «العمليات غير المرسلة».
///
/// Arabic: تجعل الطابور مرئياً بدل أن يكون صندوقاً أسود. الحالة الحرجة هي العملية
/// المرفوضة نهائياً: لا يجوز حذفها بصمت لأن ذلك يعني ضياع تسجيل حلقة كاملة دون أن
/// يدري المعلّم — فتُعرض له مع سبب الرفض وخياري إعادة المحاولة أو التخلّي الصريح.
/// EN: Makes the outbox visible; permanently rejected writes must be surfaced,
/// never silently dropped.
class SyncQueuePage extends StatefulWidget {
  const SyncQueuePage({super.key});

  @override
  State<SyncQueuePage> createState() => _SyncQueuePageState();
}

class _SyncQueuePageState extends State<SyncQueuePage> {
  late Future<List<OutboxEntry>> _future;

  @override
  void initState() {
    super.initState();
    _future = sl<SyncManager>().entries();
  }

  void _reload() {
    setState(() => _future = sl<SyncManager>().entries());
  }

  Future<void> _retry(OutboxEntry entry) async {
    await sl<SyncManager>().retryEntry(entry.id!);
    if (mounted) _reload();
  }

  Future<void> _discard(OutboxEntry entry) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('حذف العملية؟'),
        content: Text(
          'سيُحذف "${entry.label}" نهائياً ولن تصل بياناته إلى الخادم. لا يمكن التراجع.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: const Color(0xFFB3261E)),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('حذف'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    await sl<SyncManager>().discardEntry(entry.id!);
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('العمليات غير المرسلة'),
        actions: [
          IconButton(
            tooltip: 'محاولة المزامنة الآن',
            icon: const Icon(Icons.sync),
            onPressed: () async {
              await sl<SyncManager>().sync();
              if (mounted) _reload();
            },
          ),
        ],
      ),
      body: FutureBuilder<List<OutboxEntry>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }

          final entries = snapshot.data ?? const <OutboxEntry>[];

          if (entries.isEmpty) {
            return const _EmptyState();
          }

          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: entries.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) => _EntryCard(
              entry: entries[index],
              onRetry: () => _retry(entries[index]),
              onDiscard: () => _discard(entries[index]),
            ),
          );
        },
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.cloud_done_outlined, size: 56, color: Color(0xFF2A7D6A)),
          SizedBox(height: 12),
          Text('كل البيانات مُزامنة', style: TextStyle(fontSize: 16)),
        ],
      ),
    );
  }
}

class _EntryCard extends StatelessWidget {
  final OutboxEntry entry;
  final VoidCallback onRetry;
  final VoidCallback onDiscard;

  const _EntryCard({
    required this.entry,
    required this.onRetry,
    required this.onDiscard,
  });

  @override
  Widget build(BuildContext context) {
    final failed = entry.status == OutboxStatus.failed;

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  failed ? Icons.error_outline : Icons.schedule,
                  size: 18,
                  color: failed ? const Color(0xFFB3261E) : const Color(0xFF6B5B2E),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    entry.label,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'سُجّل: ${_formatDateTime(entry.clientRecordedAt)}'
              '${entry.attempts > 0 ? ' • محاولات: ${entry.attempts}' : ''}',
              style: const TextStyle(fontSize: 12, color: Colors.black54),
            ),
            if (failed && entry.lastError != null) ...[
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFB3261E).withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  entry.lastError!,
                  style: const TextStyle(fontSize: 12, color: Color(0xFFB3261E)),
                ),
              ),
            ],
            const SizedBox(height: 4),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (failed)
                  TextButton.icon(
                    onPressed: onDiscard,
                    icon: const Icon(Icons.delete_outline, size: 18),
                    label: const Text('حذف'),
                    style: TextButton.styleFrom(foregroundColor: const Color(0xFFB3261E)),
                  ),
                TextButton.icon(
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh, size: 18),
                  label: const Text('إعادة المحاولة'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  static String _formatDateTime(DateTime v) {
    String two(int n) => n.toString().padLeft(2, '0');
    return '${v.year}-${two(v.month)}-${two(v.day)} ${two(v.hour)}:${two(v.minute)}';
  }
}
