import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/models.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService api;
  final FlutterSecureStorage _secure = const FlutterSecureStorage();

  User? user;
  String? token;
  bool loading = false;

  AuthProvider(this.api);

  bool get isAuthenticated => token != null && token!.isNotEmpty;

  Future<void> init() async {
    try {
      token = await _secure.read(key: 'ittube_token');
    } catch (_) {
      final prefs = await SharedPreferences.getInstance();
      token = prefs.getString('ittube_token');
    }

    if (token == null || token!.isEmpty) return;
    api.client.token = token;
    try {
      user = await api.me();
    } catch (_) {
      await _clearToken();
    }
    notifyListeners();
  }

  Future<void> login(String login, String password) async {
    loading = true;
    notifyListeners();
    try {
      final json = await api.login(login, password);
      final data = json['data'] as Map<String, dynamic>;
      token = data['token'] as String;
      api.client.token = token;
      await _saveToken(token!);
      user = User.fromJson(data['user'] as Map<String, dynamic>);
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> register({
    required String username,
    required String email,
    required String password,
    String? displayName,
  }) async {
    loading = true;
    notifyListeners();
    try {
      final json = await api.register(username: username, email: email, password: password, displayName: displayName);
      final data = json['data'] as Map<String, dynamic>;
      token = data['token'] as String;
      api.client.token = token;
      await _saveToken(token!);
      user = User.fromJson(data['user'] as Map<String, dynamic>);
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  /// Signs in with a Google ID token obtained on the device.
  /// The backend verifies the token signature, audience and expiry.
  Future<void> signInWithGoogle(String idToken) async {
    loading = true;
    notifyListeners();
    try {
      final json = await api.googleLogin(idToken);
      final data = json['data'] as Map<String, dynamic>;
      token = data['token'] as String;
      api.client.token = token;
      await _saveToken(token!);
      user = User.fromJson(data['user'] as Map<String, dynamic>);
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  /// Adopts a token minted by the backend's browser sign-in flow.
  Future<void> loginWithToken(String value) async {
    loading = true;
    notifyListeners();
    try {
      token = value;
      api.client.token = value;
      await _saveToken(value);
      user = await api.me();
    } catch (_) {
      await _clearToken();
      rethrow;
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> refresh() async {
    if (!isAuthenticated) return;
    try {
      user = await api.me();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> updateProfile({String? displayName, String? bio, String? country, File? avatar}) async {
    user = await api.updateProfile(displayName: displayName, bio: bio, country: country, avatar: avatar);
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await api.logout();
    } catch (_) {}
    await _clearToken();
    user = null;
    notifyListeners();
  }

  Future<void> deleteAccount(String password) async {
    await api.deleteAccount(password);
    await _clearToken();
    user = null;
    notifyListeners();
  }

  Future<void> _saveToken(String value) async {
    try {
      await _secure.write(key: 'ittube_token', value: value);
    } catch (_) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('ittube_token', value);
    }
  }

  Future<void> _clearToken() async {
    token = null;
    api.client.token = null;
    try {
      await _secure.delete(key: 'ittube_token');
    } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('ittube_token');
  }
}
