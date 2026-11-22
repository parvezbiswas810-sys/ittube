import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/feed_widgets.dart';
import '../../widgets/widgets.dart';
import '../notifications/notifications_screen.dart';
import '../search/search_screen.dart';
import 'home_tabs.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabs = TabController(length: 5, vsync: this);

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Column(
        children: [
          Container(
            color: Colors.white,
            child: SafeArea(
              bottom: false,
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(14, 6, 8, 0),
                    child: Row(
                      children: [
                        const AppLogo(size: 30),
                        const SizedBox(width: 9),
                        const Text(
                          'IT TUBE',
                          style: TextStyle(fontSize: 16.5, fontWeight: FontWeight.w900, letterSpacing: 1.6, color: AppColors.navy),
                        ),
                        const Spacer(),
                        const _NotificationBell(),
                        IconButton(
                          onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SearchScreen())),
                          icon: const Icon(Icons.search_rounded, size: 23, color: AppColors.navy),
                          tooltip: 'Search',
                        ),
                      ],
                    ),
                  ),
                  TabBar(
                    controller: _tabs,
                    isScrollable: true,
                    tabAlignment: TabAlignment.start,
                    padding: const EdgeInsets.only(left: 8, right: 8),
                    dividerColor: Colors.transparent,
                    indicatorSize: TabBarIndicatorSize.tab,
                    indicator: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(10)),
                    labelColor: AppColors.navy,
                    unselectedLabelColor: AppColors.muted,
                    labelStyle: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800),
                    unselectedLabelStyle: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600),
                    tabs: const [
                      Tab(text: 'Home'),
                      Tab(text: 'Subs'),
                      Tab(text: 'Music'),
                      Tab(text: 'Live'),
                      Tab(text: 'More'),
                    ],
                  ),
                  const Divider(height: 1, color: AppColors.border),
                ],
              ),
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabs,
              children: const [
                HomeFeedTab(),
                SubsTab(),
                MusicTab(),
                LiveTab(),
                MoreTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Bell with an unread dot driven by the notifications endpoint.
class _NotificationBell extends StatefulWidget {
  const _NotificationBell();

  @override
  State<_NotificationBell> createState() => _NotificationBellState();
}

class _NotificationBellState extends State<_NotificationBell> {
  int _unread = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    if (!context.read<AuthProvider>().isAuthenticated) return;
    try {
      final (items, unread) = await context.read<ApiService>().notifications();
      if (!mounted) return;
      setState(() => _unread = unread > 0 ? unread : items.where((n) => !n.isRead).length);
    } catch (_) {
      // A failed badge refresh must never break the header.
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          onPressed: () async {
            await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const NotificationsScreen()));
            if (mounted) _load();
          },
          icon: const Icon(Icons.notifications_none_rounded, size: 23, color: AppColors.navy),
          tooltip: 'Notifications',
        ),
        if (_unread > 0)
          Positioned(
            right: 9,
            top: 7,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
              decoration: BoxDecoration(color: AppColors.danger, borderRadius: BorderRadius.circular(9), border: Border.all(color: Colors.white, width: 1.5)),
              child: Text(
                _unread > 9 ? '9+' : '$_unread',
                style: const TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w800),
              ),
            ),
          ),
      ],
    );
  }
}

/// Home tab: Shorts shelf followed by the paginated full-width feed.
class HomeFeedTab extends StatefulWidget {
  const HomeFeedTab({super.key});

  @override
  State<HomeFeedTab> createState() => _HomeFeedTabState();
}

class _HomeFeedTabState extends State<HomeFeedTab> with AutomaticKeepAliveClientMixin {
  final ScrollController _scroll = ScrollController();
  final List<Video> _videos = [];
  List<Video> _shorts = const [];
  int _page = 1;
  bool _hasMore = true;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _refresh());
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scroll.hasClients || _loadingMore || !_hasMore) return;
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 420) {
      _loadMore();
    }
  }

  Future<void> _refresh() async {
    setState(() {
      _error = null;
      _loading = _videos.isEmpty;
    });
    try {
      final api = context.read<ApiService>();
      final (videos, pagination) = await api.videos(page: 1);

      List<Video> shorts = const [];
      if (context.read<ConfigProvider>().config.shortsEnabled) {
        try {
          shorts = await api.shorts();
        } catch (_) {
          shorts = const [];
        }
      }

      if (!mounted) return;
      setState(() {
        _videos
          ..clear()
          ..addAll(videos);
        _shorts = shorts;
        _page = 1;
        _hasMore = pagination.hasMore;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore) return;
    setState(() => _loadingMore = true);
    try {
      final (videos, pagination) = await context.read<ApiService>().videos(page: _page + 1);
      if (!mounted) return;
      setState(() {
        _videos.addAll(videos);
        _page += 1;
        _hasMore = pagination.hasMore;
      });
    } catch (_) {
      // Keep the current page; the user can pull to refresh.
    } finally {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (_loading) {
      return ListView(children: const [FeedSkeletonList()]);
    }

    if (_error != null && _videos.isEmpty) {
      return RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _refresh,
        child: ListView(
          children: [SizedBox(height: 340, child: ErrorState(message: _error!, onRetry: _refresh))],
        ),
      );
    }

    if (_videos.isEmpty && _shorts.isEmpty) {
      return RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _refresh,
        child: ListView(
          children: const [
            SizedBox(height: 140),
            EmptyState(title: 'Nothing here yet', message: 'New uploads will appear here. Pull down to refresh.'),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _refresh,
      child: ListView.builder(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 24),
        itemCount: _videos.length + 2,
        itemBuilder: (context, index) {
          if (index == 0) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ShortsRow(shorts: _shorts),
                const ContinueWatchingRow(),
              ],
            );
          }

          final i = index - 1;
          if (i < _videos.length) return FeedVideoCard(video: _videos[i]);

          if (_loadingMore) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.4))),
            );
          }
          if (!_hasMore) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 22),
              child: Center(child: Text("You're all caught up", style: TextStyle(fontSize: 12, color: AppColors.muted))),
            );
          }
          return const SizedBox(height: 40);
        },
      ),
    );
  }
}
