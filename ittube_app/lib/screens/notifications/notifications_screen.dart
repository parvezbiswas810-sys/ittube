import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/notification_provider.dart';
import '../../widgets/widgets.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<NotificationProvider>().load());
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<NotificationProvider>();
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (provider.items.any((n) => !n.isRead))
            TextButton(onPressed: provider.markAllRead, child: const Text('Mark all read', style: TextStyle(fontSize: 12))),
        ],
      ),
      body: !auth.isAuthenticated
          ? const EmptyState(title: 'Sign in for notifications', message: 'Subscriber, comment and upload alerts appear here.', icon: Icons.notifications_rounded)
          : provider.loading
              ? const Center(child: CircularProgressIndicator())
              : provider.items.isEmpty
                  ? const EmptyState(title: 'No notifications', message: 'You are all caught up.', icon: Icons.notifications_none_rounded)
                  : RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: provider.load,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: provider.items.length,
                        separatorBuilder: (_, __) => const Divider(height: 1, indent: 68),
                        itemBuilder: (context, i) {
                          final n = provider.items[i];
                          return ListTile(
                            onTap: () => provider.markRead(n.id),
                            leading: Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: n.isRead ? AppColors.soft : AppColors.light,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(
                                n.type == 'new_subscriber'
                                    ? Icons.person_add_alt_1_rounded
                                    : n.type == 'new_upload'
                                        ? Icons.video_call_rounded
                                        : n.type == 'comment_reply'
                                            ? Icons.chat_bubble_outline_rounded
                                            : Icons.campaign_rounded,
                                color: AppColors.primary,
                                size: 20,
                              ),
                            ),
                            title: Text(n.title, style: TextStyle(fontWeight: n.isRead ? FontWeight.w500 : FontWeight.w700, fontSize: 13.5, color: AppColors.navy)),
                            subtitle: Text(
                              [if (n.body != null && n.body!.isNotEmpty) n.body!, timeAgo(n.createdAt)].join(' · '),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
                            ),
                            trailing: n.isRead ? null : Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle)),
                          );
                        },
                      ),
                    ),
    );
  }
}
