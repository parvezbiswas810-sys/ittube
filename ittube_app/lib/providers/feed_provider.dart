import 'package:flutter/foundation.dart';

import '../models/models.dart';
import '../services/api_service.dart';

class FeedProvider extends ChangeNotifier {
  final ApiService api;
  List<HomeSection> sections = [];
  bool loading = false;
  String? error;

  FeedProvider(this.api);

  Future<void> load({bool refresh = false}) async {
    if (loading) return;
    loading = true;
    if (refresh) error = null;
    notifyListeners();
    try {
      sections = await api.home();
      error = null;
    } catch (e) {
      error = e.toString();
    }
    loading = false;
    notifyListeners();
  }
}
