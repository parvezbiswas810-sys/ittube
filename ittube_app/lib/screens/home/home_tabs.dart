import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/auth_gate.dart';
import '../../widgets/feed_widgets.dart';
import '../../widgets/widgets.dart';
import '../channel/channel_screen.dart';
import '../downloads/downloads_screen.dart';
import '../explore/explore_screen.dart';
import '../library/library_screen.dart';
import '../notifications/notifications_screen.dart';
import '../profile/profile_screen.dart';
import '../search/search_screen.dart';

/// Subscribed channels plus their latest uploads.
class SubsTab extends StatefulWidget {
  const SubsTab({super.key});

  @override
  State<SubsTab> createState() => _SubsTabState();
}

class _SubsTabState extends State<SubsTab> with AutomaticKeepAliveClientMixin {
  final ScrollController _scroll = ScrollController();
  List<Channel> _channels = const [];
  final List<Video> _videos = [];
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.hasClients && !_loadingMore && _hasMore && _scroll.position.pixels >= _scroll.position.maxScrollExtent - 400) {
        _loadMore();
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      setState(() => _loading = false);
      return;
    }
    setState(() => _loading = true);
    try {
      final api = context.read<ApiService>();
      final channels = await api.subscriptions();
      final (videos, pagination) = await api.subscriptionFeed(page: 1);
      if (!mounted) return;
      setState(() {
        _channels = channels;
        _videos
          ..clear()
          ..addAll(videos);
        _page = 1;
        _hasMore = pagination.hasMore;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _loadMore() async {
    setState(() => _loadingMore = true);
    try {
      final (videos, pagination) = await context.read<ApiService>().subscriptionFeed(page: _page + 1);
      if (!mounted) return;
      setState(() {
        _videos.addAll(videos);
        _page += 1;
        _hasMore = pagination.hasMore;
      });
    } catch (_) {
      // Ignore and keep the current page.
    } finally {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final authed = context.watch<AuthProvider>().isAuthenticated;

    if (!authed) {
      return ListView(
        children: [
          const SizedBox(height: 90),
          EmptyState(
            icon: Icons.subscriptions_outlined,
            title: 'Sign in to see your subscriptions',
            message: 'Subscribe to creators and their newest videos appear here.',
            action: FilledButton(
              onPressed: () async {
                if (await ensureSignedIn(context, action: 'see your subscriptions') && mounted) _load();
              },
              style: FilledButton.styleFrom(minimumSize: const Size(190, 46)),
              child: const Text('Continue with Google'),
            ),
          ),
        ],
      );
    }

    if (_loading) return ListView(children: const [FeedSkeletonList(count: 2)]);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 12, bottom: 24),
        itemCount: _videos.length + 2,
        itemBuilder: (context, index) {
          if (index == 0) return _channelStrip();
          final i = index - 1;
          if (i < _videos.length) return FeedVideoCard(video: _videos[i]);
          if (_loadingMore) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.2))),
            );
          }
          if (_videos.isEmpty) {
            return const Padding(
              padding: EdgeInsets.only(top: 40),
              child: EmptyState(
                icon: Icons.playlist_add_check_rounded,
                title: 'No new videos',
                message: 'Subscribe to more creators to fill this feed.',
              ),
            );
          }
          return const SizedBox(height: 30);
        },
      ),
    );
  }

  Widget _channelStrip() {
    if (_channels.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 92,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _channels.length,
        separatorBuilder: (_, __) => const SizedBox(width: 14),
        itemBuilder: (context, i) {
          final channel = _channels[i];
          return GestureDetector(
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChannelScreenRoute(handle: channel.handle))),
            child: SizedBox(
              width: 62,
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(2),
                    decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: AppColors.primary.withValues(alpha: 0.35), width: 2)),
                    child: ChannelAvatar(channel: channel, size: 52),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    channel.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 10.5, color: AppColors.navy, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Thin wrapper so the channel screen import stays in one place.
class ChannelScreenRoute extends StatelessWidget {
  final String handle;
  const ChannelScreenRoute({super.key, required this.handle});

  @override
  Widget build(BuildContext context) => ChannelScreen(handle: handle);
}

/// Music discovery driven by the admin-managed categories.
class MusicTab extends StatefulWidget {
  const MusicTab({super.key});

  @override
  State<MusicTab> createState() => _MusicTabState();
}

class _MusicTabState extends State<MusicTab> with AutomaticKeepAliveClientMixin {
  final List<Video> _videos = [];
  bool _loading = true;
  String? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final api = context.read<ApiService>();
      List<Video> videos;
      try {
        final categories = await api.categories();
        Category? music;
        for (final category in categories) {
          if (category.name.toLowerCase().contains('music')) {
            music = category;
            break;
          }
        }
        videos = music == null ? (await api.videos(q: 'music')).$1 : (await api.videos(category: music.id.toString())).$1;
      } catch (_) {
        videos = (await api.videos(q: 'music')).$1;
      }
      if (!mounted) return;
      setState(() {
        _videos
          ..clear()
          ..addAll(videos);
        _loading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    if (_loading) return ListView(children: const [FeedSkeletonList(count: 2)]);
    if (_error != null && _videos.isEmpty) {
      return ListView(children: [SizedBox(height: 320, child: ErrorState(message: _error!, onRetry: _load))]);
    }
    if (_videos.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 110),
          EmptyState(icon: Icons.music_note_rounded, title: 'No music yet', message: 'Music uploads from creators will show up here.'),
        ],
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 12, bottom: 24),
        itemCount: _videos.length + 1,
        itemBuilder: (context, index) {
          if (index == 0) {
            return Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
              child: Row(
                children: [
                  Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(9)),
                    child: const Icon(Icons.music_note_rounded, size: 17, color: Colors.white),
                  ),
                  const SizedBox(width: 10),
                  const Text('Music', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.navy)),
                ],
              ),
            );
          }
          return FeedVideoCard(video: _videos[index - 1]);
        },
      ),
    );
  }
}

/// Live and scheduled broadcasts from the Laravel live API.
class LiveTab extends StatefulWidget {
  const LiveTab({super.key});

  @override
  State<LiveTab> createState() => _LiveTabState();
}

class _LiveTabState extends State<LiveTab> with AutomaticKeepAliveClientMixin {
  List<LiveStream> _streams = const [];
  bool _loading = true;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    if (!context.read<ConfigProvider>().config.liveStreamEnabled) {
      setState(() => _loading = false);
      return;
    }
    try {
      final streams = await context.read<ApiService>().liveStreams();
      if (!mounted) return;
      setState(() {
        _streams = streams;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final enabled = context.watch<ConfigProvider>().config.liveStreamEnabled;

    if (!enabled) {
      return ListView(
        children: const [
          SizedBox(height: 110),
          EmptyState(
            icon: Icons.sensors_off_rounded,
            title: 'Live is not enabled',
            message: 'The admin can turn on live streaming from the admin panel. Check back soon.',
          ),
        ],
      );
    }

    if (_loading) return ListView(children: const [FeedSkeletonList(count: 2)]);

    if (_streams.isEmpty) {
      return RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _load,
        child: ListView(
          children: const [
            SizedBox(height: 110),
            EmptyState(icon: Icons.sensors_rounded, title: 'No live streams', message: 'Live and scheduled broadcasts will appear here.'),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(top: 12, bottom: 24),
        itemCount: _streams.length,
        itemBuilder: (context, i) => LiveCard(stream: _streams[i]),
      ),
    );
  }
}

/// Categories and quick links.
class MoreTab extends StatefulWidget {
  const MoreTab({super.key});

  @override
  State<MoreTab> createState() => _MoreTabState();
}

class _MoreTabState extends State<MoreTab> with AutomaticKeepAliveClientMixin {
  List<Category> _categories = const [];
  bool _loading = true;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    try {
      final categories = await context.read<ApiService>().categories();
      if (!mounted) return;
      setState(() {
        _categories = categories;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
      children: [
        const Text('Browse', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.navy)),
        const SizedBox(height: 12),
        if (_loading)
          const Padding(padding: EdgeInsets.symmetric(vertical: 20), child: Center(child: CircularProgressIndicator(strokeWidth: 2.2)))
        else if (_categories.isEmpty)
          const Text('No categories yet.', style: TextStyle(fontSize: 12.5, color: AppColors.muted))
        else
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              for (final category in _categories)
                GestureDetector(
                  onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => SearchScreen(initialQuery: category.name))),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                    decoration: BoxDecoration(color: AppColors.lavenderSoft, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 26,
                          height: 26,
                          decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(8)),
                          child: const Icon(Icons.tag_rounded, size: 14, color: Colors.white),
                        ),
                        const SizedBox(width: 8),
                        Text(category.name, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppColors.navy)),
                      ],
                    ),
                  ),
                ),
            ],
          ),
        const SizedBox(height: 26),
        const Text('Shortcuts', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.navy)),
        const SizedBox(height: 10),
        _tile(context, Icons.explore_outlined, 'Explore', 'Trending creators and categories', () => const ExploreScreen()),
        _tile(context, Icons.video_library_outlined, 'Library', 'History, playlists and your uploads', () => const LibraryScreen()),
        _tile(context, Icons.download_done_rounded, 'Downloads', 'Videos saved on this device', () => const DownloadsScreen()),
        _tile(context, Icons.notifications_none_rounded, 'Notifications', 'Subscriptions and announcements', () => const NotificationsScreen()),
        _tile(context, Icons.person_outline_rounded, 'Account & settings', 'Profile, privacy and playback', () => const ProfileScreen()),
      ],
    );
  }

  Widget _tile(BuildContext context, IconData icon, String title, String subtitle, Widget Function() builder) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border), boxShadow: AppShadows.card),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        leading: Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(12)),
          child: Icon(icon, size: 19, color: AppColors.primary),
        ),
        title: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.navy)),
        subtitle: Text(subtitle, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => builder())),
      ),
    );
  }
}
