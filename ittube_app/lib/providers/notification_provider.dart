import 'package:flutter/foundation.dart';

import '../models/models.dart';
import '../services/api_service.dart';

class NotificationProvider extends ChangeNotifier {
  final ApiService api;
  List<NotificationItem> items = [];
  int unread = 0;
  bool loading = false;

  NotificationProvider(this.api);

  Future<void> load() async {
    loading = true;
    notifyListeners();
    try {
      final (list, unreadCount) = await api.notifications();
      items = list;
      unread = unreadCount;
    } catch (_) {}
    loading = false;
    notifyListeners();
  }

  Future<void> markAllRead() async {
    await api.readAllNotifications();
    items = items.map((n) => NotificationItem(id: n.id, type: n.type, title: n.title, body: n.body, isRead: true, createdAt: n.createdAt)).toList();
    unread = 0;
    notifyListeners();
  }

  Future<void> markRead(int id) async {
    await api.readNotification(id);
    await load();
  }
}
