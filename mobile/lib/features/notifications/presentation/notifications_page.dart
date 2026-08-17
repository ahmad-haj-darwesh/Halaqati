import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';
import 'bloc/notifications_cubit.dart'; // تأكد من مطابقة المسار للكيوبت الذي أنشأناه

class NotificationsPage extends StatelessWidget {
  const NotificationsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final apiClient = ApiClient(tokenStorage: SecureTokenStorage());
        return NotificationsCubit(apiClient)..loadInitial();
      },
      child: const _NotificationsView(),
    );
  }
}

class _NotificationsView extends StatelessWidget {
  const _NotificationsView();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<NotificationsCubit, NotificationsState>(
      builder: (context, state) {
        final bool canMarkAllRead = state is NotificationsLoaded && state.items.isNotEmpty && state.hasUnread;

        return Scaffold(
          appBar: AppBar(
            title: const Text('الإشعارات'),
            actions: [
              TextButton(
                onPressed: canMarkAllRead 
                  ? () => context.read<NotificationsCubit>().markAllAsRead()
                  : null,
                child: const Text('تعليم الكل مقروءاً'),
              ),
            ],
          ),
          body: RefreshIndicator(
            onRefresh: () => context.read<NotificationsCubit>().loadInitial(),
            child: _buildBody(context, state),
          ),
        );
      },
    );
  }

  Widget _buildBody(BuildContext context, NotificationsState state) {
    if (state is NotificationsLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state is NotificationsError) {
      return ListView(
        children: [
          const SizedBox(height: 80),
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Text(state.message, textAlign: TextAlign.center),
          ),
          const SizedBox(height: 16),
          Center(
            child: FilledButton(
              onPressed: () => context.read<NotificationsCubit>().loadInitial(),
              child: const Text('إعادة المحاولة'),
            ),
          ),
        ],
      );
    }

    if (state is NotificationsLoaded) {
      if (state.items.isEmpty) {
        return ListView(
          children: const [
            SizedBox(height: 120),
            Icon(Icons.notifications_none_rounded, size: 72, color: Colors.grey),
            SizedBox(height: 16),
            Center(child: Text('لا توجد إشعارات بعد')),
          ],
        );
      }

      return ListView.builder(
        itemCount: state.items.length + (state.hasReachedMax ? 0 : 1),
        itemBuilder: (context, i) {
          // زر "تحميل المزيد"
          if (i >= state.items.length) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(8.0),
                child: state.isFetchingMore
                    ? const CircularProgressIndicator()
                    : TextButton(
                        onPressed: () => context.read<NotificationsCubit>().loadMore(),
                        child: const Text('تحميل المزيد'),
                      ),
              ),
            );
          }

          final n = state.items[i];
          final unread = n['is_read'] != true;
          
          return Card(
            margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            color: unread ? const Color(0xFF2563EB).withValues(alpha: 0.06) : Colors.white,
            child: ListTile(
              leading: unread
                  ? const Icon(Icons.circle, size: 10, color: Color(0xFF2563EB))
                  : const SizedBox(width: 10),
              title: Text(
                n['title']?.toString() ?? '', 
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
              ),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 4),
                  Text(
                    n['body']?.toString() ?? '', 
                    style: TextStyle(fontSize: 13, color: Colors.grey.shade800),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    n['created_at_human']?.toString() ?? '',
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                  ),
                ],
              ),
              onTap: () {
                final id = n['id'] as int?;
                if (id != null && unread) {
                  context.read<NotificationsCubit>().markAsRead(id);
                }
              },
            ),
          );
        },
      );
    }

    return const SizedBox();
  }
}