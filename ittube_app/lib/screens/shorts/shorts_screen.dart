import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../channel/channel_screen.dart';
import '../video/comments_sheet.dart';

class ShortsScreen extends StatefulWidget {
  const ShortsScreen({super.key});

  @override
  State<ShortsScreen> createState() => _ShortsScreenState();
}

class _ShortsScreenState extends State<ShortsScreen> {
  final PageController _pageController = PageController();
  List<Video> _shorts = [];
  bool _loading = true;
  String? _error;
  int _current = 0;

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
      final shorts = await context.read<ApiService>().shorts();
      if (!mounted) return;
      setState(() {
        _shorts = shorts;
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

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: const Text('Shorts', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
        actions: [
          IconButton(onPressed: _load, icon: const Icon(Icons.refresh_rounded, color: Colors.white)),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Colors.white))
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : _shorts.isEmpty
                  ? const EmptyState(title: 'No shorts yet', message: 'Short videos uploaded from the app will appear here.', icon: Icons.bolt_rounded)
                  : PageView.builder(
                      controller: _pageController,
                      scrollDirection: Axis.vertical,
                      itemCount: _shorts.length,
                      onPageChanged: (i) => setState(() => _current = i),
                      itemBuilder: (context, i) => ShortPlayer(
                        video: _shorts[i],
                        active: i == _current,
                      ),
                    ),
    );
  }
}

class ShortPlayer extends StatefulWidget {
  final Video video;
  final bool active;
  const ShortPlayer({super.key, required this.video, required this.active});

  @override
  State<ShortPlayer> createState() => _ShortPlayerState();
}

class _ShortPlayerState extends State<ShortPlayer> {
  VideoPlayerController? _controller;
  bool _ready = false;
  bool _liked = false;
  bool _subscribed = false;
  int _likes = 0;

  @override
  void initState() {
    super.initState();
    _liked = widget.video.myReaction == 'like';
    _likes = widget.video.likesCount;
    _subscribed = widget.video.channel?.isSubscribed ?? false;
    _init();
  }

  Future<void> _init() async {
    final url = widget.video.playbackUrl;
    if (url == null || url.isEmpty) return;
    final controller = VideoPlayerController.networkUrl(Uri.parse(url));
    _controller = controller;
    try {
      await controller.initialize();
      await controller.setLooping(true);
      if (widget.active && mounted) {
        await controller.play();
        if (mounted) setState(() => _ready = true);
      }
      controller.addListener(() {
        if (mounted) setState(() {});
      });
    } catch (_) {
      if (mounted) setState(() => _ready = false);
    }
  }

  @override
  void didUpdateWidget(covariant ShortPlayer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.active != widget.active && _controller != null) {
      if (widget.active) {
        _controller!.play();
        context.read<ApiService>().recordView(widget.video.id).catchError((_) => <String, dynamic>{});
      } else {
        _controller!.pause();
        _controller!.seekTo(Duration.zero);
      }
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _toggleLike() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sign in to like shorts.')));
      return;
    }
    try {
      final result = await context.read<ApiService>().react(widget.video.id, 'like');
      final data = result['data'] as Map<String, dynamic>;
      setState(() {
        _liked = data['state'] == 'like';
        _likes = data['likes'] is int ? data['likes'] as int : _likes;
      });
    } catch (_) {}
  }

  Future<void> _toggleSubscribe() async {
    final auth = context.read<AuthProvider>();
    final channel = widget.video.channel;
    if (!auth.isAuthenticated || channel == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sign in to subscribe.')));
      return;
    }
    try {
      if (_subscribed) {
        await context.read<ApiService>().unsubscribe(channel.id);
      } else {
        await context.read<ApiService>().subscribe(channel.id);
      }
      setState(() => _subscribed = !_subscribed);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        GestureDetector(
          onTap: () {
            final c = _controller;
            if (c == null) return;
            c.value.isPlaying ? c.pause() : c.play();
            setState(() {});
          },
          child: _ready && _controller != null
              ? FittedBox(
                  fit: BoxFit.cover,
                  child: SizedBox(
                    width: _controller!.value.size.width,
                    height: _controller!.value.size.height,
                    child: VideoPlayer(_controller!),
                  ),
                )
              : Container(
                  color: Colors.black,
                  child: Center(
                    child: widget.video.thumbnailUrl != null
                        ? Image.network(widget.video.thumbnailUrl!, fit: BoxFit.cover)
                        : const Icon(Icons.play_circle_outline_rounded, color: Colors.white54, size: 60),
                  ),
                ),
        ),
        const Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Colors.transparent, Colors.transparent, Color(0xCC000000)],
                stops: [0, 0.6, 1],
              ),
            ),
          ),
        ),
        Positioned(
          left: 16,
          right: 84,
          bottom: 30,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              GestureDetector(
                onTap: () {
                  final channel = widget.video.channel;
                  if (channel != null) {
                    Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChannelScreen(handle: channel.handle)));
                  }
                },
                child: Row(
                  children: [
                    ChannelAvatar(channel: widget.video.channel, size: 38),
                    const SizedBox(width: 10),
                    Flexible(
                      child: Text(
                        '@${widget.video.channel?.handle ?? 'ittube'}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13.5),
                      ),
                    ),
                    const SizedBox(width: 10),
                    GestureDetector(
                      onTap: _toggleSubscribe,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                        decoration: BoxDecoration(
                          color: _subscribed ? Colors.white24 : AppColors.primary,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white54, width: 0.6),
                        ),
                        child: Text(_subscribed ? 'Subscribed' : 'Subscribe', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Text(widget.video.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600, height: 1.35)),
              const SizedBox(height: 4),
              Text('${formatCount(widget.video.viewsCount)} views · ${timeAgo(widget.video.publishedAt)}', style: const TextStyle(color: Colors.white70, fontSize: 11.5)),
            ],
          ),
        ),
        Positioned(
          right: 10,
          bottom: 40,
          child: Column(
            children: [
              _action(Icons.favorite_rounded, formatCount(_likes), _liked ? AppColors.cyan : Colors.white, _toggleLike),
              const SizedBox(height: 18),
              _action(Icons.chat_bubble_rounded, formatCount(widget.video.commentsCount), Colors.white, () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  builder: (_) => CommentsSheet(video: widget.video),
                );
              }),
              const SizedBox(height: 18),
              _action(Icons.share_rounded, 'Share', Colors.white, () {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Share link: ${widget.video.playbackUrl ?? ''}')));
              }),
            ],
          ),
        ),
        if (_controller != null && _controller!.value.isInitialized)
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: VideoProgressIndicator(_controller!, allowScrubbing: true, colors: const VideoProgressColors(playedColor: AppColors.cyan, bufferedColor: Colors.white24, backgroundColor: Colors.white12)),
          ),
      ],
    );
  }

  Widget _action(IconData icon, String label, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.28), shape: BoxShape.circle),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(height: 3),
          Text(label, style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
