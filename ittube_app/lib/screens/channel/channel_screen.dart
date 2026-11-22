import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';

class ChannelScreen extends StatefulWidget {
  final String handle;
  const ChannelScreen({super.key, required this.handle});

  @override
  State<ChannelScreen> createState() => _ChannelScreenState();
}

class _ChannelScreenState extends State<ChannelScreen> {
  Channel? _channel;
  List<Video> _videos = [];
  List<Video> _shorts = [];
  bool _loading = true;
  String? _error;
  bool _subscribed = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await context.read<ApiService>().channel(widget.handle);
      if (!mounted) return;
      final channel = Channel.fromJson(data['channel'] as Map<String, dynamic>);
      setState(() {
        _channel = channel;
        _subscribed = channel.isSubscribed;
        _videos = (data['videos'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
        _shorts = (data['shorts'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
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

  Future<void> _toggleSubscribe() async {
    final channel = _channel;
    if (channel == null) return;
    if (!context.read<AuthProvider>().isAuthenticated) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sign in to subscribe.')));
      return;
    }
    try {
      if (_subscribed) {
        await context.read<ApiService>().unsubscribe(channel.id);
      } else {
        await context.read<ApiService>().subscribe(channel.id);
      }
      setState(() {
        _subscribed = !_subscribed;
        _channel = channel.copyWith(
          isSubscribed: _subscribed,
          subscribersCount: _subscribed ? channel.subscribersCount + 1 : (channel.subscribersCount - 1).clamp(0, 1 << 31),
        );
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final channel = _channel;

    return Scaffold(
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null || channel == null
              ? ErrorState(message: _error ?? 'Channel not found', onRetry: _load)
              : NestedScrollView(
                  headerSliverBuilder: (context, inner) => [
                    SliverAppBar(
                      pinned: true,
                      expandedHeight: 190,
                      backgroundColor: Colors.white,
                      foregroundColor: AppColors.navy,
                      flexibleSpace: FlexibleSpaceBar(
                        background: Stack(
                          fit: StackFit.expand,
                          children: [
                            if (channel.bannerUrl != null)
                              CachedNetworkImage(imageUrl: channel.bannerUrl!, fit: BoxFit.cover)
                            else
                              const DecoratedBox(
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                    colors: [AppColors.orbit, AppColors.primary, AppColors.cyan],
                                  ),
                                ),
                              ),
                            const DecoratedBox(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                  colors: [Colors.transparent, Color(0x99000000)],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(16, 14, 16, 6),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                ChannelAvatar(channel: channel, size: 66),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Flexible(
                                            child: Text(channel.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: AppColors.navy)),
                                          ),
                                          if (channel.isVerified) ...[
                                            const SizedBox(width: 5),
                                            const Icon(Icons.verified_rounded, size: 17, color: AppColors.primary),
                                          ],
                                        ],
                                      ),
                                      Text(
                                        '@${channel.handle} · ${formatCount(channel.subscribersCount)} subscribers · ${channel.videosCount} videos',
                                        style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: FilledButton(
                                    onPressed: _toggleSubscribe,
                                    style: FilledButton.styleFrom(
                                      minimumSize: const Size.fromHeight(42),
                                      backgroundColor: _subscribed ? AppColors.light : AppColors.primary,
                                      foregroundColor: _subscribed ? AppColors.primary : Colors.white,
                                    ),
                                    child: Text(_subscribed ? 'Subscribed' : 'Subscribe'),
                                  ),
                                ),
                              ],
                            ),
                            if ((channel.description ?? '').isNotEmpty) ...[
                              const SizedBox(height: 12),
                              Text(channel.description!, style: const TextStyle(fontSize: 12.5, color: AppColors.text, height: 1.5)),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ],
                  body: DefaultTabController(
                    length: 3,
                    child: Column(
                      children: [
                        const TabBar(tabs: [Tab(text: 'Videos'), Tab(text: 'Shorts'), Tab(text: 'About')]),
                        Expanded(
                          child: TabBarView(
                            children: [
                              _videos.isEmpty
                                  ? const EmptyState(title: 'No videos yet', message: 'This channel has not published videos.', icon: Icons.videocam_off_outlined)
                                  : ListView.builder(
                                      padding: const EdgeInsets.only(top: 6, bottom: 20),
                                      itemCount: _videos.length,
                                      itemBuilder: (context, i) => VideoCard(video: _videos[i], compact: true),
                                    ),
                              _shorts.isEmpty
                                  ? const EmptyState(title: 'No shorts yet', message: 'This channel has not published shorts.', icon: Icons.bolt_rounded)
                                  : GridView.builder(
                                      padding: const EdgeInsets.all(12),
                                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, childAspectRatio: 0.75, crossAxisSpacing: 10, mainAxisSpacing: 10),
                                      itemCount: _shorts.length,
                                      itemBuilder: (context, i) {
                                        final short = _shorts[i];
                                        return ClipRRect(
                                          borderRadius: BorderRadius.circular(12),
                                          child: Stack(
                                            fit: StackFit.expand,
                                            children: [
                                              if (short.thumbnailUrl != null)
                                                CachedNetworkImage(imageUrl: short.thumbnailUrl!, fit: BoxFit.cover)
                                              else
                                                Container(color: AppColors.light),
                                              const DecoratedBox(
                                                decoration: BoxDecoration(
                                                  gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Color(0xAA000000)]),
                                                ),
                                              ),
                                              Positioned(
                                                left: 8,
                                                right: 8,
                                                bottom: 8,
                                                child: Text(short.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 11.5, fontWeight: FontWeight.w600)),
                                              ),
                                              Positioned(
                                                top: 8,
                                                left: 8,
                                                child: Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(5)),
                                                  child: Text('${formatCount(short.viewsCount)} views', style: const TextStyle(color: Colors.white, fontSize: 9.5)),
                                                ),
                                              ),
                                            ],
                                          ),
                                        );
                                      },
                                    ),
                              ListView(
                                padding: const EdgeInsets.all(16),
                                children: [
                                  const Text('About this channel', style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.navy)),
                                  const SizedBox(height: 10),
                                  Text(channel.description ?? 'No description provided.', style: const TextStyle(fontSize: 13, height: 1.6, color: AppColors.text)),
                                  const SizedBox(height: 16),
                                  Container(
                                    padding: const EdgeInsets.all(14),
                                    decoration: BoxDecoration(color: AppColors.ice, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
                                    child: Column(
                                      children: [
                                        _row('Handle', '@${channel.handle}'),
                                        _row('Subscribers', formatCount(channel.subscribersCount)),
                                        _row('Videos', '${channel.videosCount}'),
                                      ],
                                    ),
                                  ),
                                  if (channel.links.isNotEmpty) ...[
                                    const SizedBox(height: 16),
                                    ...channel.links.map((l) => ListTile(
                                          contentPadding: EdgeInsets.zero,
                                          leading: const Icon(Icons.link_rounded, color: AppColors.primary, size: 20),
                                          title: Text(l.platform, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                                          subtitle: Text(l.url, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
                                        )),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Text(label, style: const TextStyle(fontSize: 12.5, color: AppColors.muted)),
          const Spacer(),
          Text(value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppColors.navy)),
        ],
      ),
    );
  }
}
