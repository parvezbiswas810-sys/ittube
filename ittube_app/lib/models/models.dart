int _int(dynamic v) => v == null ? 0 : (v is int ? v : int.tryParse(v.toString()) ?? 0);
bool _bool(dynamic v) => v == true || v == 1 || v == '1' || v == 'true';
String? _str(dynamic v) => v == null ? null : v.toString();

class Pagination {
  final int currentPage;
  final int lastPage;
  final int total;
  final bool hasMore;

  const Pagination({this.currentPage = 1, this.lastPage = 1, this.total = 0, this.hasMore = false});

  factory Pagination.fromJson(Map<String, dynamic>? json) => Pagination(
        currentPage: _int(json?['current_page'] ?? 1),
        lastPage: _int(json?['last_page'] ?? 1),
        total: _int(json?['total'] ?? 0),
        hasMore: _bool(json?['has_more']),
      );
}

class User {
  final int id;
  final String username;
  final String displayName;
  final String? email;
  final String? avatarUrl;
  final String role;
  final String status;
  final String? bio;
  final bool emailVerified;
  final Channel? channel;

  const User({
    required this.id,
    required this.username,
    required this.displayName,
    this.email,
    this.avatarUrl,
    this.role = 'user',
    this.status = 'active',
    this.bio,
    this.emailVerified = false,
    this.channel,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: _int(json['id']),
        username: json['username']?.toString() ?? '',
        displayName: json['display_name']?.toString() ?? json['username']?.toString() ?? '',
        email: _str(json['email']),
        avatarUrl: _str(json['avatar_url']),
        role: json['role']?.toString() ?? 'user',
        status: json['status']?.toString() ?? 'active',
        bio: _str(json['bio']),
        emailVerified: _bool(json['email_verified']),
        channel: json['channel'] is Map<String, dynamic> ? Channel.fromJson(json['channel']) : null,
      );
}

class ChannelLink {
  final String platform;
  final String url;
  const ChannelLink({required this.platform, required this.url});
  factory ChannelLink.fromJson(Map<String, dynamic> j) => ChannelLink(platform: j['platform']?.toString() ?? '', url: j['url']?.toString() ?? '');
}

class Channel {
  final int id;
  final String name;
  final String handle;
  final String? avatarUrl;
  final String? bannerUrl;
  final String? description;
  final int subscribersCount;
  final int videosCount;
  final bool isVerified;
  final bool isSubscribed;
  final List<ChannelLink> links;

  const Channel({
    required this.id,
    required this.name,
    required this.handle,
    this.avatarUrl,
    this.bannerUrl,
    this.description,
    this.subscribersCount = 0,
    this.videosCount = 0,
    this.isVerified = false,
    this.isSubscribed = false,
    this.links = const [],
  });

  factory Channel.fromJson(Map<String, dynamic> json) => Channel(
        id: _int(json['id']),
        name: json['name']?.toString() ?? '',
        handle: json['handle']?.toString() ?? '',
        avatarUrl: _str(json['avatar_url']),
        bannerUrl: _str(json['banner_url']),
        description: _str(json['description']),
        subscribersCount: _int(json['subscribers_count']),
        videosCount: _int(json['videos_count']),
        isVerified: _bool(json['is_verified']),
        isSubscribed: _bool(json['is_subscribed']),
        links: (json['links'] as List?)?.whereType<Map<String, dynamic>>().map(ChannelLink.fromJson).toList() ?? const [],
      );

  Channel copyWith({int? subscribersCount, bool? isSubscribed}) => Channel(
        id: id,
        name: name,
        handle: handle,
        avatarUrl: avatarUrl,
        bannerUrl: bannerUrl,
        description: description,
        subscribersCount: subscribersCount ?? this.subscribersCount,
        videosCount: videosCount,
        isVerified: isVerified,
        isSubscribed: isSubscribed ?? this.isSubscribed,
        links: links,
      );
}

class Category {
  final int id;
  final String name;
  final String slug;
  final String? iconUrl;
  final int videosCount;

  const Category({required this.id, required this.name, required this.slug, this.iconUrl, this.videosCount = 0});

  factory Category.fromJson(Map<String, dynamic> json) => Category(
        id: _int(json['id']),
        name: json['name']?.toString() ?? '',
        slug: json['slug']?.toString() ?? '',
        iconUrl: _str(json['icon_url']),
        videosCount: _int(json['videos_count']),
      );
}

class Video {
  final int id;
  final String title;
  final String? description;
  final String type;
  final String? thumbnailUrl;
  final String? playbackUrl;
  final int duration;
  final String durationText;
  final int viewsCount;
  final int likesCount;
  final int dislikesCount;
  final int commentsCount;
  final String? myReaction;
  final String status;
  final String visibility;
  final bool isFeatured;
  final bool allowComments;
  final bool allowDownload;
  final String? publishedAt;
  final Channel? channel;
  final Category? category;
  final List<String> tags;

  const Video({
    required this.id,
    required this.title,
    this.description,
    this.type = 'video',
    this.thumbnailUrl,
    this.playbackUrl,
    this.duration = 0,
    this.durationText = '0:00',
    this.viewsCount = 0,
    this.likesCount = 0,
    this.dislikesCount = 0,
    this.commentsCount = 0,
    this.myReaction,
    this.status = 'published',
    this.visibility = 'public',
    this.isFeatured = false,
    this.allowComments = true,
    this.allowDownload = false,
    this.publishedAt,
    this.channel,
    this.category,
    this.tags = const [],
  });

  factory Video.fromJson(Map<String, dynamic> json) => Video(
        id: _int(json['id']),
        title: json['title']?.toString() ?? '',
        description: _str(json['description']),
        type: json['type']?.toString() ?? 'video',
        thumbnailUrl: _str(json['thumbnail_url']),
        playbackUrl: _str(json['playback_url']),
        duration: _int(json['duration']),
        durationText: json['duration_text']?.toString() ?? '0:00',
        viewsCount: _int(json['views_count']),
        likesCount: _int(json['likes_count']),
        dislikesCount: _int(json['dislikes_count']),
        commentsCount: _int(json['comments_count']),
        myReaction: _str(json['my_reaction']),
        status: json['status']?.toString() ?? 'published',
        visibility: json['visibility']?.toString() ?? 'public',
        isFeatured: _bool(json['is_featured']),
        allowComments: _bool(json['allow_comments']),
        allowDownload: _bool(json['allow_download']),
        publishedAt: _str(json['published_at']),
        channel: json['channel'] is Map<String, dynamic> ? Channel.fromJson(json['channel']) : null,
        category: json['category'] is Map<String, dynamic> ? Category.fromJson(json['category']) : null,
        tags: (json['tags'] as List?)?.map((e) => e.toString()).toList() ?? const [],
      );

  Video copyWith({int? likesCount, int? dislikesCount, int? commentsCount, String? myReaction, Channel? channel}) => Video(
        id: id,
        title: title,
        description: description,
        type: type,
        thumbnailUrl: thumbnailUrl,
        playbackUrl: playbackUrl,
        duration: duration,
        durationText: durationText,
        viewsCount: viewsCount,
        likesCount: likesCount ?? this.likesCount,
        dislikesCount: dislikesCount ?? this.dislikesCount,
        commentsCount: commentsCount ?? this.commentsCount,
        myReaction: myReaction,
        status: status,
        visibility: visibility,
        isFeatured: isFeatured,
        allowComments: allowComments,
        allowDownload: allowDownload,
        publishedAt: publishedAt,
        channel: channel ?? this.channel,
        category: category,
        tags: tags,
      );
}

class Comment {
  final int id;
  final String body;
  final int likesCount;
  final String? createdAt;
  final bool isMine;
  final User? user;
  final List<Comment> replies;

  const Comment({required this.id, required this.body, this.likesCount = 0, this.createdAt, this.isMine = false, this.user, this.replies = const []});

  factory Comment.fromJson(Map<String, dynamic> json) => Comment(
        id: _int(json['id']),
        body: json['body']?.toString() ?? '',
        likesCount: _int(json['likes_count']),
        createdAt: _str(json['created_at']),
        isMine: _bool(json['is_mine']),
        user: json['user'] is Map<String, dynamic> ? User.fromJson(json['user']) : null,
        replies: (json['replies'] as List?)?.whereType<Map<String, dynamic>>().map(Comment.fromJson).toList() ?? const [],
      );
}

class Playlist {
  final int id;
  final String title;
  final String? description;
  final String visibility;
  final int videosCount;
  final List<Video> videos;

  const Playlist({required this.id, required this.title, this.description, this.visibility = 'public', this.videosCount = 0, this.videos = const []});

  factory Playlist.fromJson(Map<String, dynamic> json) => Playlist(
        id: _int(json['id']),
        title: json['title']?.toString() ?? '',
        description: _str(json['description']),
        visibility: json['visibility']?.toString() ?? 'public',
        videosCount: _int(json['videos_count']),
        videos: (json['videos'] as List?)?.whereType<Map<String, dynamic>>().map(Video.fromJson).toList() ?? const [],
      );
}

class NotificationItem {
  final int id;
  final String type;
  final String title;
  final String? body;
  final bool isRead;
  final String? createdAt;

  const NotificationItem({required this.id, required this.type, required this.title, this.body, this.isRead = false, this.createdAt});

  factory NotificationItem.fromJson(Map<String, dynamic> json) => NotificationItem(
        id: _int(json['id']),
        type: json['type']?.toString() ?? '',
        title: json['title']?.toString() ?? '',
        body: _str(json['body']),
        isRead: _bool(json['is_read']),
        createdAt: _str(json['created_at']),
      );
}

class HistoryItem {
  final int historyId;
  final int position;
  final Video video;
  const HistoryItem({required this.historyId, required this.position, required this.video});
  factory HistoryItem.fromJson(Map<String, dynamic> json) => HistoryItem(
        historyId: _int(json['history_id']),
        position: _int(json['position']),
        video: Video.fromJson(json['video'] as Map<String, dynamic>),
      );
}

class HomeSection {
  final int id;
  final String title;
  final String type;
  final List<Video> videos;
  const HomeSection({required this.id, required this.title, required this.type, this.videos = const []});
  factory HomeSection.fromJson(Map<String, dynamic> json) => HomeSection(
        id: _int(json['id']),
        title: json['title']?.toString() ?? '',
        type: json['type']?.toString() ?? 'custom',
        videos: (json['videos'] as List?)?.whereType<Map<String, dynamic>>().map(Video.fromJson).toList() ?? const [],
      );
}

class AppConfig {
  final String appName;
  final String version;
  final String? announcement;
  final bool maintenance;
  final String maintenanceMessage;
  final bool registrationEnabled;
  final bool uploadsEnabled;
  final bool commentsEnabled;
  final bool shortsEnabled;
  final bool googleSignInEnabled;
  final String? googleClientId;
  final bool guestModeEnabled;
  final bool downloadsEnabled;
  final bool liveStreamEnabled;
  final int maxUploadSizeMb;
  final List<String> allowedFormats;
  final String? termsUrl;
  final String? privacyUrl;
  final String? supportUrl;

  const AppConfig({
    this.appName = 'IT TUBE',
    this.version = '1.0.0',
    this.announcement,
    this.maintenance = false,
    this.maintenanceMessage = 'IT TUBE is under maintenance.',
    this.registrationEnabled = true,
    this.uploadsEnabled = true,
    this.commentsEnabled = true,
    this.shortsEnabled = true,
    this.googleSignInEnabled = false,
    this.googleClientId,
    this.guestModeEnabled = true,
    this.downloadsEnabled = false,
    this.liveStreamEnabled = false,
    this.maxUploadSizeMb = 512,
    this.allowedFormats = const ['mp4', 'mov', 'mkv', 'webm'],
    this.termsUrl,
    this.privacyUrl,
    this.supportUrl,
  });

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    final maintenance = json['maintenance'] as Map<String, dynamic>? ?? {};
    final features = json['features'] as Map<String, dynamic>? ?? {};
    final uploads = json['uploads'] as Map<String, dynamic>? ?? {};
    final links = json['links'] as Map<String, dynamic>? ?? {};
    final auth = json['auth'] as Map<String, dynamic>? ?? {};

    return AppConfig(
      appName: json['app_name']?.toString() ?? 'IT TUBE',
      version: json['version']?.toString() ?? '1.0.0',
      announcement: _str(json['announcement']),
      maintenance: _bool(maintenance['enabled']),
      maintenanceMessage: maintenance['message']?.toString() ?? 'IT TUBE is under maintenance.',
      registrationEnabled: _bool(features['registration']),
      uploadsEnabled: _bool(features['uploads']),
      commentsEnabled: _bool(features['comments']),
      shortsEnabled: _bool(features['shorts']),
      googleSignInEnabled: _bool(features['google_signin']) || _bool(json['google_login_enabled']),
      googleClientId: _str(auth['google_client_id']) ?? _str(json['google_client_id']),
      guestModeEnabled: auth['guest_mode_enabled'] != null
          ? _bool(auth['guest_mode_enabled'])
          : (json['guest_mode_enabled'] == null ? true : _bool(json['guest_mode_enabled'])),
      downloadsEnabled: _bool(json['downloads_enabled']) || _bool(features['downloads']),
      liveStreamEnabled: _bool(json['live_stream_enabled']) || _bool(features['live_stream']),
      maxUploadSizeMb: _int(uploads['max_size_mb']),
      allowedFormats: (uploads['formats'] as List?)?.map((e) => e.toString()).toList() ?? const ['mp4'],
      termsUrl: _str(links['terms']),
      privacyUrl: _str(links['privacy']),
      supportUrl: _str(links['support']),
    );
  }
}

class UploadLimits {
  final int maxUploadMb;
  final int maxDirectUploadMb;
  final int chunkSize;
  final List<String> allowedFormats;
  final bool uploadsEnabled;

  const UploadLimits({
    this.maxUploadMb = 512,
    this.maxDirectUploadMb = 8,
    this.chunkSize = 4 * 1024 * 1024,
    this.allowedFormats = const ['mp4', 'mov', 'mkv', 'webm'],
    this.uploadsEnabled = true,
  });

  factory UploadLimits.fromJson(Map<String, dynamic> json) => UploadLimits(
        maxUploadMb: _int(json['max_upload_mb']) > 0 ? _int(json['max_upload_mb']) : 512,
        maxDirectUploadMb: _int(json['max_direct_upload_mb']) > 0 ? _int(json['max_direct_upload_mb']) : 8,
        chunkSize: _int(json['chunk_size']) > 0 ? _int(json['chunk_size']) : 4 * 1024 * 1024,
        allowedFormats: (json['allowed_formats'] as List? ?? []).map((e) => e.toString()).toList(),
        uploadsEnabled: json['uploads_enabled'] != false,
      );

  bool canUploadDirectly(int bytes) => bytes <= maxDirectUploadMb * 1024 * 1024;
}
