import 'dart:io';

import '../core/api_client.dart';
import '../models/models.dart';

class ApiService {
  final ApiClient client;
  ApiService(this.client);

  String get baseUrl => client.baseUrl;

  // ---------------- Auth ----------------
  Future<Map<String, dynamic>> register({
    required String username,
    required String email,
    required String password,
    String? displayName,
  }) =>
      client.post('auth/register', body: {
        'username': username,
        'email': email,
        'password': password,
        'password_confirmation': password,
        'display_name': displayName ?? username,
        'terms_accepted': 1,
        'device_name': 'android',
      });

  Future<Map<String, dynamic>> login(String login, String password) =>
      client.post('auth/login', body: {'login': login, 'password': password, 'device_name': 'android'});

  Future<User> me() async => User.fromJson((await client.get('auth/me'))['data'] as Map<String, dynamic>);

  Future<void> logout() => client.post('auth/logout');

  Future<void> forgotPassword(String email) => client.post('auth/forgot-password', body: {'email': email});

  Future<void> resetPassword({required String email, required String token, required String password}) =>
      client.post('auth/reset-password', body: {
        'email': email,
        'token': token,
        'password': password,
        'password_confirmation': password,
      });

  Future<void> deleteAccount(String password) => client.post('auth/delete-account', body: {'password': password});

  Future<Map<String, dynamic>> googleLogin(String idToken) =>
      client.post('auth/google', body: {'id_token': idToken, 'device_name': 'android'});

  Future<void> registerDeviceToken(String token, String platform) =>
      client.post('auth/device-tokens', body: {'token': token, 'platform': platform});

  Future<void> removeDeviceToken(String token) => client.delete('auth/device-tokens');

  // ---------------- Config ----------------
  Future<AppConfig> appConfig() async {
    final data = (await client.get('app/config'))['data'] as Map<String, dynamic>;
    return AppConfig.fromJson(data);
  }

  // ---------------- Home & feed ----------------
  Future<List<HomeSection>> home() async {
    final data = (await client.get('home'))['data'] as Map<String, dynamic>;
    return (data['sections'] as List? ?? []).whereType<Map<String, dynamic>>().map(HomeSection.fromJson).toList();
  }

  Future<(List<Video>, Pagination)> videos({int page = 1, String? sort, String? category, String? channel, String? q, String type = 'video'}) async {
    final json = await client.get('videos', query: {
      'page': page,
      'per_page': 15,
      if (sort != null) 'sort': sort,
      if (category != null) 'category': category,
      if (channel != null) 'channel': channel,
      if (q != null) 'q': q,
      'type': type,
    });
    final list = (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
    return (list, Pagination.fromJson(json['pagination'] as Map<String, dynamic>?));
  }

  Future<List<Video>> shorts({int page = 1}) async {
    final json = await client.get('shorts', query: {'page': page, 'per_page': 10});
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
  }

  Future<Video> video(int id) async => Video.fromJson((await client.get('videos/$id'))['data'] as Map<String, dynamic>);

  Future<List<Video>> related(int id) async {
    final json = await client.get('videos/$id/related');
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
  }

  Future<Map<String, dynamic>> recordView(int id, {int position = 0, bool completed = false}) =>
      client.post('videos/$id/view', body: {'position': position, 'completed': completed});

  Future<Map<String, dynamic>> react(int id, String type) => client.post('videos/$id/$type');

  Future<void> report({required String type, required int id, required String reason, String? details}) =>
      client.post('reports', body: {'type': type, 'id': id, 'reason': reason, 'details': details});

  // ---------------- Search ----------------
  Future<Map<String, dynamic>> search(String query, {String filter = 'all', int page = 1}) =>
      client.get('search', query: {'q': query, 'filter': filter, 'page': page});

  // ---------------- Categories & channels ----------------
  Future<List<LiveStream>> liveStreams() async {
    final json = await client.get('live-streams');
    final data = json['data'];
    final raw = data is Map<String, dynamic> ? (data['streams'] as List? ?? const []) : (data as List? ?? const []);
    return raw.map((e) => LiveStream.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Category>> categories() async {
    final json = await client.get('categories');
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Category.fromJson).toList();
  }

  Future<List<Channel>> channels({int page = 1, String? q, bool featured = false}) async {
    final json = await client.get('channels', query: {'page': page, 'per_page': 20, if (q != null) 'q': q, if (featured) 'featured': 1});
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Channel.fromJson).toList();
  }

  Future<Map<String, dynamic>> channel(String handle) async =>
      (await client.get('channels/$handle'))['data'] as Map<String, dynamic>;

  Future<Channel> myChannel() async => Channel.fromJson((await client.get('channels/mine'))['data'] as Map<String, dynamic>);

  Future<Channel> createChannel({
    required String name,
    required String handle,
    String? description,
    int? categoryId,
    File? avatar,
    File? banner,
  }) async {
    final json = await client.upload('channels', fields: {
      'name': name,
      'handle': handle,
      if (description != null) 'description': description,
      if (categoryId != null) 'category_id': categoryId.toString(),
    }, file: avatar, fieldName: 'avatar', extraFiles: {if (banner != null) 'banner': banner});
    return Channel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Channel> updateChannel({
    required int id,
    String? name,
    String? description,
    int? categoryId,
    File? avatar,
    File? banner,
  }) async {
    final json = await client.upload('channels/$id', fields: {
      '_method': 'PUT',
      if (name != null) 'name': name,
      if (description != null) 'description': description,
      if (categoryId != null) 'category_id': categoryId.toString(),
    }, file: avatar, fieldName: 'avatar', extraFiles: {if (banner != null) 'banner': banner});
    return Channel.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> downloadVideo(int id, String destinationPath, {void Function(int bytes)? onProgress}) async {
    final file = File(destinationPath);
    final sink = file.openWrite();
    try {
      await client.downloadTo('videos/$id/download', sink, onProgress: onProgress);
    } finally {
      await sink.close();
    }
  }

  Future<Map<String, dynamic>> subscribe(int channelId) => client.post('channels/$channelId/subscribe');

  Future<Map<String, dynamic>> unsubscribe(int channelId) => client.delete('channels/$channelId/subscribe');

  Future<List<Channel>> subscriptions() async {
    final json = await client.get('subscriptions');
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Channel.fromJson).toList();
  }

  Future<(List<Video>, Pagination)> subscriptionFeed({int page = 1}) async {
    final json = await client.get('subscriptions/feed', query: {'page': page});
    final list = (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
    return (list, Pagination.fromJson(json['pagination'] as Map<String, dynamic>?));
  }

  // ---------------- Comments ----------------
  Future<(List<Comment>, Pagination)> comments(int videoId, {int page = 1}) async {
    final json = await client.get('videos/$videoId/comments', query: {'page': page});
    final list = (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Comment.fromJson).toList();
    return (list, Pagination.fromJson(json['pagination'] as Map<String, dynamic>?));
  }

  Future<Comment> addComment(int videoId, String body, {int? parentId}) async => Comment.fromJson(
      (await client.post('videos/$videoId/comments', body: {'body': body, if (parentId != null) 'parent_id': parentId}))['data']
          as Map<String, dynamic>);

  Future<void> deleteComment(int id) => client.delete('comments/$id');

  Future<Map<String, dynamic>> likeComment(int id) => client.post('comments/$id/like');

  // ---------------- Playlists ----------------
  Future<List<Playlist>> playlists() async {
    final json = await client.get('playlists');
    return (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Playlist.fromJson).toList();
  }

  Future<Playlist> createPlaylist(String title, {String? description, String visibility = 'public'}) async =>
      Playlist.fromJson((await client.post('playlists', body: {
        'title': title,
        'description': description,
        'visibility': visibility,
      }))['data'] as Map<String, dynamic>);

  Future<void> addToPlaylist(int playlistId, int videoId) => client.post('playlists/$playlistId/videos', body: {'video_id': videoId});

  Future<void> removeFromPlaylist(int playlistId, int videoId) => client.delete('playlists/$playlistId/videos/$videoId');

  // ---------------- History ----------------
  Future<(List<HistoryItem>, Pagination)> history({int page = 1}) async {
    final json = await client.get('history', query: {'page': page});
    final list = (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(HistoryItem.fromJson).toList();
    return (list, Pagination.fromJson(json['pagination'] as Map<String, dynamic>?));
  }

  Future<void> clearHistory() => client.delete('history');

  Future<void> removeHistory(int id) => client.delete('history/$id');

  // ---------------- Notifications ----------------
  Future<(List<NotificationItem>, int)> notifications({int page = 1}) async {
    final json = await client.get('notifications', query: {'page': page});
    final data = json['data'] as Map<String, dynamic>;
    final list = (data['notifications'] as List? ?? []).whereType<Map<String, dynamic>>().map(NotificationItem.fromJson).toList();
    return (list, data['unread_count'] is int ? data['unread_count'] as int : 0);
  }

  Future<void> readNotification(int id) => client.post('notifications/$id/read');

  Future<void> readAllNotifications() => client.post('notifications/read-all');

  Future<Map<String, dynamic>> notificationPreferences() async =>
      (await client.get('notifications/preferences'))['data'] as Map<String, dynamic>;

  Future<void> updateNotificationPreferences(Map<String, dynamic> values) =>
      client.put('notifications/preferences', body: values);

  // ---------------- Profile ----------------
  Future<User> updateProfile({String? displayName, String? bio, String? country, File? avatar}) async {
    if (avatar != null) {
      final json = await client.upload('profile', fields: {
        if (displayName != null) 'display_name': displayName,
        if (bio != null) 'bio': bio,
        if (country != null) 'country': country,
        '_method': 'PUT',
      }, file: avatar, fieldName: 'avatar');
      return User.fromJson(json['data'] as Map<String, dynamic>);
    }
    final json = await client.put('profile', body: {
      if (displayName != null) 'display_name': displayName,
      if (bio != null) 'bio': bio,
      if (country != null) 'country': country,
    });
    return User.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> changePassword(String current, String next) =>
      client.post('profile/password', body: {'current_password': current, 'password': next, 'password_confirmation': next});

  // ---------------- Uploads ----------------
  Future<Video> uploadVideo({
    required File file,
    required String title,
    String? description,
    int? categoryId,
    String visibility = 'public',
    String type = 'video',
    String? tags,
    File? thumbnail,
    void Function(double progress)? onProgress,
  }) async {
    final json = await client.upload('videos', fields: {
      'title': title,
      if (description != null) 'description': description,
      if (categoryId != null) 'category_id': categoryId.toString(),
      'visibility': visibility,
      'type': type,
      if (tags != null) 'tags': tags,
    }, file: file, thumbnail: thumbnail, onProgress: onProgress);

    final data = json['data'] as Map<String, dynamic>;
    return Video.fromJson(data['video'] as Map<String, dynamic>);
  }

  Future<(List<Video>, Pagination)> myUploads({int page = 1}) async {
    final json = await client.get('videos/mine', query: {'page': page});
    final list = (json['data'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
    return (list, Pagination.fromJson(json['pagination'] as Map<String, dynamic>?));
  }

  // ---------------- Resumable uploads ----------------
  Future<UploadLimits> uploadLimits() async {
    final data = (await client.get('videos/upload/limits'))['data'] as Map<String, dynamic>;
    return UploadLimits.fromJson(data);
  }

  /// Uploads in chunks so files larger than the PHP post limit still work.
  /// Returns the created video; [onProgress] receives 0..1.
  Future<Video> uploadVideoResumable({
    required File file,
    required String title,
    String? description,
    int? categoryId,
    String visibility = 'public',
    String type = 'video',
    String? tags,
    File? thumbnail,
    UploadLimits? limits,
    void Function(double progress, String stage)? onProgress,
    bool Function()? isCancelled,
  }) async {
    final info = limits ?? await uploadLimits();
    final total = await file.length();

    if (total > info.maxUploadMb * 1024 * 1024) {
      throw ApiException('This file is ${(total / 1048576).toStringAsFixed(1)} MB. The limit is ${info.maxUploadMb} MB.');
    }

    final chunkSize = info.chunkSize.clamp(256 * 1024, 8 * 1024 * 1024);
    final totalChunks = (total / chunkSize).ceil();

    final init = await client.post('videos/chunk/init', body: {
      'filename': file.uri.pathSegments.last,
      'size': total,
      'total_chunks': totalChunks,
      'title': title,
      if (description != null) 'description': description,
      if (categoryId != null) 'category_id': categoryId,
      'visibility': visibility,
      'type': type,
      if (tags != null) 'tags': tags,
    });

    final uploadId = (init['data'] as Map<String, dynamic>)['upload_id'] as String;

    try {
      final raf = await file.open();
      try {
        var sent = 0;
        for (var index = 0; index < totalChunks; index++) {
          if (isCancelled?.call() ?? false) {
            throw const UploadCancelledException();
          }

          final start = index * chunkSize;
          final length = (start + chunkSize <= total) ? chunkSize : total - start;
          await raf.setPosition(start);
          final bytes = await raf.read(length);

          await client.uploadBytes(
            'videos/chunk/$uploadId',
            fieldName: 'chunk',
            filename: 'part_$index.bin',
            bytes: bytes,
            fields: {'index': '$index'},
          );

          sent += length;
          onProgress?.call(
            (sent / total).clamp(0.0, 0.99),
            'Uploading ${((sent / total) * 100).toStringAsFixed(0)}% · chunk ${index + 1}/$totalChunks',
          );
        }
      } finally {
        await raf.close();
      }

      onProgress?.call(0.99, 'Processing video…');

      final complete = await client.uploadWithThumbnail(
        'videos/chunk/$uploadId/complete',
        fields: const {},
        thumbnail: thumbnail,
      );

      onProgress?.call(1, 'Done');
      final data = complete['data'] as Map<String, dynamic>;
      return Video.fromJson(data['video'] as Map<String, dynamic>);
    } catch (error) {
      try {
        await client.delete('videos/chunk/$uploadId');
      } catch (_) {}
      rethrow;
    }
  }

  Future<void> deleteVideo(int id) => client.delete('videos/$id');
}
