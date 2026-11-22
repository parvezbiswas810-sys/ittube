import 'package:flutter/foundation.dart';

import '../models/models.dart';
import '../services/api_service.dart';

class ConfigProvider extends ChangeNotifier {
  final ApiService api;
  AppConfig config = const AppConfig();
  bool loaded = false;
  String? error;

  ConfigProvider(this.api);

  Future<void> load() async {
    try {
      config = await api.appConfig();
      error = null;
    } catch (e) {
      error = e.toString();
    }
    loaded = true;
    notifyListeners();
  }
}
