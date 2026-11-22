import 'dart:async';
import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:video_player/video_player.dart';
import 'package:wakelock_plus/wakelock_plus.dart';

import '../../core/api_client.dart';
import '../../core/route_observer.dart';
import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../widgets/auth_gate.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../channel/channel_screen.dart';
import 'comments_sheet.dart';

class VideoPlayerScreen extends StatefulWidget {
  final Video video;
  final Duration? startAt;
  const VideoPlayerScreen({super.key, required this.video, this.startAt});

  @override
  State<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends State<VideoPlayerScreen> with WidgetsBindingObserver, RouteAware {
  late Video _video = widget.video;
  VideoPlayerController? _controller;
  late final ApiService _api;
  Timer? _progressTimer;
  Timer? _controlsTimer;
  bool _ready = false;
  bool _showPoster = true;
  bool _controls = true;
  bool _autoNext = true;
  bool _completed = false;
  bool _resumeOnForeground = false;
  bool _resumeAfterRoute = false;
  bool _lastPlaying = true;
  bool _lastBuffering = false;
  DateTime _lastTick = DateTime.now();
  String? _seekHint;
  double? _seekTapX;
  bool _fullscreen = false;
  bool _subscribed = false;
  List<Video> _related = [];
  String? _error;

  bool get _isVisible => ModalRoute.of(context)?.isCurrent ?? true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _api = context.read<ApiService>();
    _subscribed = _video.channel?.isSubscribed ?? false;
    _init();
    _loadRelated();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final route = ModalRoute.of(context);
    if (route is PageRoute) appRouteObserver.subscribe(this, route);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized) return;

    if (state == AppLifecycleState.paused || state == AppLifecycleState.inactive || state == AppLifecycleState.hidden) {
      if (controller.value.isPlaying) {
        _resumeOnForeground = true;
        controller.pause();
        _saveProgress();
      }
    } else if (state == AppLifecycleState.resumed && _resumeOnForeground) {
      _resumeOnForeground = false;
      if (_isVisible) controller.play();
    }
  }

  @override
  void didPushNext() {
    final controller = _controller;
    if (controller != null && controller.value.isPlaying) {
      _resumeAfterRoute = true;
      controller.pause();
    }
  }

  @override
  void didPopNext() {
    final controller = _controller;
    if (controller != null && _resumeAfterRoute) {
      _resumeAfterRoute = false;
      controller.play();
    }
  }

  Future<void> _init() async {
    final url = _video.playbackUrl;
    if (url == null || url.isEmpty) {
      setState(() => _error = 'This video has no playable file.');
      return;
    }

    final controller = VideoPlayerController.networkUrl(Uri.parse(url));
    _controller = controller;

    try {
      await controller.initialize();
      if (!mounted) return;

      final startAt = widget.startAt;
      if (startAt != null && startAt > Duration.zero && startAt < controller.value.duration) {
        await controller.seekTo(startAt);
      }

      controller.addListener(_onTick);
      setState(() => _ready = true);

      // Let the first frame render before audio starts so playback never
      // begins behind the loading poster.
      await Future<void>.delayed(const Duration(milliseconds: 160));
      if (!mounted || !_isVisible) return;

      await controller.play();
      WakelockPlus.enable();
      _startProgressTimer();
      _startControlsTimer();
      _api.recordView(_video.id).catchError((_) => <String, dynamic>{});
    } catch (e) {
      if (mounted) setState(() => _error = 'Unable to play this video. Please try again.');
    }
  }

  void _onTick() {
    final controller = _controller;
    if (controller == null || !mounted) return;
    final value = controller.value;

    if (_showPoster && value.position > const Duration(milliseconds: 150)) {
      setState(() => _showPoster = false);
    }

    if (!_completed && value.isInitialized && value.duration > Duration.zero && value.position >= value.duration - const Duration(milliseconds: 600)) {
      _handleCompleted();
      return;
    }

    // The platform reports progress many times per second; rebuild at most
    // twice per second unless the play/buffer state actually changed.
    final now = DateTime.now();
    final stateChanged = _lastPlaying != value.isPlaying || _lastBuffering != value.isBuffering;
    if (stateChanged || now.difference(_lastTick).inMilliseconds >= 500) {
      _lastTick = now;
      _lastPlaying = value.isPlaying;
      _lastBuffering = value.isBuffering;
      setState(() {});
    }
  }

  Future<void> _handleCompleted() async {
    if (_completed) return;
    _completed = true;
    _saveProgress(completed: true);

    if (!_autoNext || _related.isEmpty || !mounted) return;
    final next = _related.first;
    await Future<void>.delayed(const Duration(milliseconds: 400));
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => VideoPlayerScreen(video: next)));
  }

  void _startProgressTimer() {
    _progressTimer?.cancel();
    _progressTimer = Timer.periodic(const Duration(seconds: 10), (_) => _saveProgress());
  }

  void _saveProgress({bool completed = false}) {
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized) return;
    final seconds = controller.value.position.inSeconds;
    if (seconds < 3 && !completed) return;
    _api.recordView(_video.id, position: seconds, completed: completed).catchError((_) => <String, dynamic>{});
  }

  void _startControlsTimer() {
    _controlsTimer?.cancel();
    _controlsTimer = Timer(const Duration(seconds: 3), () {
      if (mounted && (_controller?.value.isPlaying ?? false)) setState(() => _controls = false);
    });
  }

  void _toggleControls() {
    final show = !_controls;
    setState(() => _controls = show);
    if (show) _startControlsTimer();
  }

  Future<void> _loadRelated() async {
    try {
      final related = await context.read<ApiService>().related(_video.id);
      if (mounted) setState(() => _related = related);
    } catch (_) {}
  }

  @override
  void dispose() {
    WakelockPlus.disable();
    _saveProgress();
    _progressTimer?.cancel();
    _controlsTimer?.cancel();
    appRouteObserver.unsubscribe(this);
    WidgetsBinding.instance.removeObserver(this);
    _controller?.removeListener(_onTick);
    _controller?.dispose();
    if (_fullscreen) {
      SystemChrome.setPreferredOrientations(DeviceOrientation.values);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
    super.dispose();
  }

  Future<void> _toggleFullscreen() async {
    setState(() => _fullscreen = !_fullscreen);
    if (_fullscreen) {
      await SystemChrome.setPreferredOrientations([DeviceOrientation.landscapeLeft, DeviceOrientation.landscapeRight]);
      await SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    } else {
      await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
      await SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
  }

  Future<void> _react(String type) async {
    if (!await ensureSignedIn(context, action: 'like videos')) return;
    if (!mounted) return;
    try {
      final result = await context.read<ApiService>().react(_video.id, type);
      final data = result['data'] as Map<String, dynamic>;
      setState(() {
        _video = _video.copyWith(
          myReaction: data['state']?.toString(),
          likesCount: data['likes'] is int ? data['likes'] as int : _video.likesCount,
          dislikesCount: data['dislikes'] is int ? data['dislikes'] as int : _video.dislikesCount,
        );
      });
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _toggleSubscribe() async {
    final channel = _video.channel;
    if (channel == null) return;
    if (!await ensureSignedIn(context, action: 'subscribe to channels')) return;
    if (!mounted) return;
    try {
      if (_subscribed) {
        await context.read<ApiService>().unsubscribe(channel.id);
      } else {
        await context.read<ApiService>().subscribe(channel.id);
      }
      setState(() => _subscribed = !_subscribed);
    } catch (_) {}
  }

  Future<void> _download() async {
    final config = context.read<ConfigProvider>().config;
    if (!config.downloadsEnabled) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Downloads are disabled by the administrator.')));
      return;
    }
    if (!_video.allowDownload) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('The creator has disabled downloads for this video.')));
      return;
    }
    if (!await ensureSignedIn(context, action: 'download videos')) return;
    if (!mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    messenger.showSnackBar(const SnackBar(content: Text('Download started…')));

    try {
      final dir = await getApplicationDocumentsDirectory();
      final folder = Directory('${dir.path}/IT TUBE');
      await folder.create(recursive: true);
      final safe = _video.title.replaceAll(RegExp(r'[^A-Za-z0-9 _-]'), '').trim();
      final path = '${folder.path}/${safe.isEmpty ? 'video' : safe}-${_video.id}.mp4';
      await context.read<ApiService>().downloadVideo(_video.id, path, onProgress: (bytes) {
        if (bytes % (1024 * 1024) < 65536) {
          messenger.showSnackBar(SnackBar(content: Text('Downloading… ${(bytes / 1048576).toStringAsFixed(1)} MB'), duration: const Duration(seconds: 1)));
        }
      });
      messenger.showSnackBar(SnackBar(content: Text('Saved to $path')));
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      messenger.showSnackBar(const SnackBar(content: Text('Download failed. Check your connection and storage space.')));
    }
  }

  Future<void> _share() async {
    final link = _video.playbackUrl ?? '';
    if (link.isEmpty) return;
    try {
      await SharePlus.instance.share(ShareParams(text: '${_video.title}\n$link', subject: _video.title));
    } catch (_) {
      await Clipboard.setData(ClipboardData(text: link));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Video link copied to clipboard.')));
      }
    }
  }

  Future<void> _saveToPlaylist() async {
    final api = context.read<ApiService>();
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sign in to save videos.')));
      return;
    }
    List<Playlist> playlists = [];
    try {
      playlists = await api.playlists();
    } catch (_) {}
    if (!mounted) return;

    await showDialog(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setDialogState) => AlertDialog(
          title: const Text('Save to playlist'),
          content: SizedBox(
            width: double.maxFinite,
            child: playlists.isEmpty
                ? const Text('You have no playlists yet. Create one below.', style: TextStyle(fontSize: 13, color: AppColors.muted))
                : ListView(
                    shrinkWrap: true,
                    children: playlists
                        .map((p) => ListTile(
                              dense: true,
                              leading: const Icon(Icons.playlist_add_rounded, color: AppColors.primary),
                              title: Text(p.title),
                              subtitle: Text('${p.videosCount} videos'),
                              onTap: () async {
                                await api.addToPlaylist(p.id, _video.id);
                                if (dialogContext.mounted) Navigator.of(dialogContext).pop();
                                if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Saved to playlist.')));
                              },
                            ))
                        .toList(),
                  ),
          ),
          actions: [
            TextButton(
              onPressed: () async {
                final controller = TextEditingController();
                final title = await showDialog<String>(
                  context: dialogContext,
                  builder: (c) => AlertDialog(
                    title: const Text('New playlist'),
                    content: TextField(controller: controller, autofocus: true, decoration: const InputDecoration(hintText: 'Playlist name')),
                    actions: [
                      TextButton(onPressed: () => Navigator.of(c).pop(), child: const Text('Cancel')),
                      FilledButton(onPressed: () => Navigator.of(c).pop(controller.text.trim()), child: const Text('Create')),
                    ],
                  ),
                );
                if (title != null && title.isNotEmpty) {
                  try {
                    final playlist = await api.createPlaylist(title);
                    await api.addToPlaylist(playlist.id, _video.id);
                    if (dialogContext.mounted) Navigator.of(dialogContext).pop();
                    if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Saved to new playlist.')));
                  } catch (_) {}
                }
              },
              child: const Text('New playlist'),
            ),
            TextButton(onPressed: () => Navigator.of(dialogContext).pop(), child: const Text('Close')),
          ],
        ),
      ),
    );
  }

  Future<void> _report() async {
    final reasons = ['spam', 'harassment', 'violence', 'sexual_content', 'copyright', 'misinformation', 'other'];
    String selected = 'spam';
    final details = TextEditingController();
    final submitted = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setDialogState) => AlertDialog(
          title: const Text('Report content'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                initialValue: selected,
                decoration: const InputDecoration(labelText: 'Reason'),
                items: reasons.map((r) => DropdownMenuItem(value: r, child: Text(r.replaceAll('_', ' ')))).toList(),
                onChanged: (v) => setDialogState(() => selected = v ?? 'spam'),
              ),
              const SizedBox(height: 12),
              TextField(controller: details, maxLines: 3, decoration: const InputDecoration(hintText: 'Additional details (optional)')),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('Cancel')),
            FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: const Text('Submit')),
          ],
        ),
      ),
    );

    if (submitted == true) {
      try {
        await context.read<ApiService>().report(
              type: _video.type == 'short' ? 'short' : 'video',
              id: _video.id,
              reason: selected,
              details: details.text.trim(),
            );
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Report submitted. Thank you.')));
      } catch (_) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Unable to submit report.')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_fullscreen) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: SafeArea(
          child: Stack(
            children: [
              Center(child: _videoArea()),
              Positioned(top: 8, left: 8, child: IconButton(onPressed: _toggleFullscreen, icon: const Icon(Icons.fullscreen_exit_rounded, color: Colors.white))),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(_video.type == 'short' ? 'Short' : 'Video', style: const TextStyle(fontSize: 16)),
        actions: [
          if (_video.allowDownload)
            IconButton(onPressed: _download, tooltip: 'Download', icon: const Icon(Icons.download_rounded, size: 21)),
          IconButton(onPressed: _report, icon: const Icon(Icons.flag_outlined, size: 20)),
        ],
      ),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          Container(color: Colors.black, child: _videoArea()),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_video.title, style: const TextStyle(fontSize: 16.5, fontWeight: FontWeight.w700, color: AppColors.navy, height: 1.35)),
                const SizedBox(height: 6),
                Text(
                  '${formatCount(_video.viewsCount)} views · ${timeAgo(_video.publishedAt)}${_video.category != null ? ' · ${_video.category!.name}' : ''}',
                  style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
                ),
                const SizedBox(height: 14),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _pillAction(
                        icon: _video.myReaction == 'like' ? Icons.thumb_up_rounded : Icons.thumb_up_outlined,
                        label: formatCount(_video.likesCount),
                        active: _video.myReaction == 'like',
                        onTap: () => _react('like'),
                      ),
                      const SizedBox(width: 8),
                      _pillAction(
                        icon: _video.myReaction == 'dislike' ? Icons.thumb_down_rounded : Icons.thumb_down_outlined,
                        label: formatCount(_video.dislikesCount),
                        active: _video.myReaction == 'dislike',
                        onTap: () => _react('dislike'),
                      ),
                      const SizedBox(width: 8),
                      _pillAction(icon: Icons.share_rounded, label: 'Share', onTap: _share),
                      const SizedBox(width: 8),
                      _pillAction(icon: Icons.playlist_add_rounded, label: 'Save', onTap: _saveToPlaylist),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: AppColors.ice, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
                  child: Row(
                    children: [
                      ChannelAvatar(channel: _video.channel, size: 42),
                      const SizedBox(width: 10),
                      Expanded(
                        child: GestureDetector(
                          onTap: () {
                            final channel = _video.channel;
                            if (channel != null) {
                              Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChannelScreen(handle: channel.handle)));
                            }
                          },
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Flexible(
                                    child: Text(
                                      _video.channel?.name ?? 'IT TUBE',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy, fontSize: 13.5),
                                    ),
                                  ),
                                  if (_video.channel?.isVerified ?? false) ...[
                                    const SizedBox(width: 4),
                                    const Icon(Icons.verified_rounded, size: 14, color: AppColors.primary),
                                  ],
                                ],
                              ),
                              Text('${formatCount(_video.channel?.subscribersCount ?? 0)} subscribers', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                            ],
                          ),
                        ),
                      ),
                      FilledButton(
                        onPressed: _toggleSubscribe,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size(96, 38),
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          backgroundColor: _subscribed ? AppColors.light : AppColors.primary,
                          foregroundColor: _subscribed ? AppColors.primary : Colors.white,
                        ),
                        child: Text(_subscribed ? 'Subscribed' : 'Subscribe', style: const TextStyle(fontSize: 12)),
                      ),
                    ],
                  ),
                ),
                if ((_video.description ?? '').isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(color: AppColors.soft, borderRadius: BorderRadius.circular(14)),
                    child: Text(_video.description!, style: const TextStyle(fontSize: 12.5, height: 1.55, color: AppColors.text)),
                  ),
                ],
                const SizedBox(height: 14),
                InkWell(
                  onTap: () => showModalBottomSheet(
                    context: context,
                    isScrollControlled: true,
                    builder: (_) => CommentsSheet(video: _video),
                  ),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(color: AppColors.ice, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
                    child: Row(
                      children: [
                        const Icon(Icons.chat_bubble_outline_rounded, size: 18, color: AppColors.primary),
                        const SizedBox(width: 8),
                        Text('${formatCount(_video.commentsCount)} comments', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.navy)),
                        const Spacer(),
                        const Icon(Icons.chevron_right_rounded, size: 18, color: AppColors.muted),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 18),
              ],
            ),
          ),
          if (_related.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 18, 10, 6),
              child: Row(
                children: [
                  const Icon(Icons.playlist_play_rounded, size: 20, color: AppColors.primary),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text('Up next', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.navy)),
                  ),
                  const Text('Autoplay', style: TextStyle(fontSize: 11.5, color: AppColors.muted, fontWeight: FontWeight.w600)),
                  Switch.adaptive(
                    value: _autoNext,
                    activeColor: AppColors.primary,
                    onChanged: (v) => setState(() => _autoNext = v),
                  ),
                ],
              ),
            ),
            ..._related.map((v) => VideoCard(video: v, compact: true)),
          ],
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _videoArea() {
    if (_error != null) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: Container(
          color: Colors.black,
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.error_outline_rounded, color: Colors.white54, size: 30),
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white70, fontSize: 12.5)),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: () {
                    setState(() {
                      _error = null;
                      _ready = false;
                      _showPoster = true;
                    });
                    _init();
                  },
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white38)),
                  icon: const Icon(Icons.refresh_rounded, size: 16),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (!_ready || _controller == null) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: Container(
          color: Colors.black,
          child: Stack(
            fit: StackFit.expand,
            children: [
              if (_video.thumbnailUrl != null && _video.thumbnailUrl!.isNotEmpty)
                CachedNetworkImage(imageUrl: _video.thumbnailUrl!, fit: BoxFit.cover)
              else
                const ColoredBox(color: Colors.black),
              const Center(child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.4)),
            ],
          ),
        ),
      );
    }

    final controller = _controller!;
    return AspectRatio(
      aspectRatio: controller.value.aspectRatio == 0 ? 16 / 9 : controller.value.aspectRatio,
      child: GestureDetector(
        onTap: _toggleControls,
        onDoubleTapDown: (details) => _seekTapX = details.localPosition.dx,
        onDoubleTap: () => _doubleTapSeek(context),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Center(child: VideoPlayer(controller)),
            if (_showPoster && _video.thumbnailUrl != null && _video.thumbnailUrl!.isNotEmpty)
              Positioned.fill(
                child: IgnorePointer(
                  child: CachedNetworkImage(imageUrl: _video.thumbnailUrl!, fit: BoxFit.cover),
                ),
              ),
            if (controller.value.isBuffering)
              const Center(child: SizedBox(width: 34, height: 34, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.6))),
            if (_seekHint != null)
              Align(
                alignment: Alignment.center,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.62), borderRadius: BorderRadius.circular(14)),
                  child: Text(_seekHint!, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14)),
                ),
              ),
            if (_controls)
              Positioned.fill(
                child: Container(
                  color: Colors.black26,
                  child: Stack(
                    children: [
                      Center(
                        child: IconButton(
                          iconSize: 58,
                          onPressed: () {
                            if (controller.value.isPlaying) {
                              controller.pause();
                              WakelockPlus.disable();
                              _saveProgress();
                              _controlsTimer?.cancel();
                            } else {
                              controller.play();
                              WakelockPlus.enable();
                              _startControlsTimer();
                            }
                            setState(() {});
                          },
                          icon: Icon(
                            controller.value.isPlaying ? Icons.pause_circle_filled_rounded : Icons.play_circle_fill_rounded,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      Positioned(
                        left: 0,
                        right: 0,
                        bottom: 0,
                        child: Column(
                          children: [
                            VideoProgressIndicator(
                              controller,
                              allowScrubbing: true,
                              colors: const VideoProgressColors(playedColor: AppColors.primary, bufferedColor: Colors.white38, backgroundColor: Colors.white12),
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
                              child: Row(
                                children: [
                                  Text(_format(controller.value.position), style: const TextStyle(color: Colors.white, fontSize: 10.5)),
                                  const Text(' / ', style: TextStyle(color: Colors.white70, fontSize: 10.5)),
                                  Text(_format(controller.value.duration), style: const TextStyle(color: Colors.white70, fontSize: 10.5)),
                                  const Spacer(),
                                  IconButton(
                                    onPressed: _toggleFullscreen,
                                    icon: const Icon(Icons.fullscreen_rounded, color: Colors.white, size: 22),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  void _doubleTapSeek(BuildContext context) {
    final controller = _controller;
    if (controller == null) return;

    final width = context.size?.width ?? MediaQuery.of(context).size.width;
    final forward = (_seekTapX ?? width) > width / 2;
    final duration = controller.value.duration;
    final target = controller.value.position + Duration(seconds: forward ? 10 : -10);
    final clamped = target < Duration.zero ? Duration.zero : (target > duration ? duration : target);

    controller.seekTo(clamped);
    HapticFeedback.selectionClick();
    setState(() => _seekHint = forward ? '\u00bb 10s' : '10s \u00ab');
    Future.delayed(const Duration(milliseconds: 650), () {
      if (mounted) setState(() => _seekHint = null);
    });
  }

  String _format(Duration d) {
    final m = d.inMinutes.remainder(60).toString();
    final s = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    return '${d.inHours > 0 ? '${d.inHours}:' : ''}$m:$s';
  }

  Widget _pillAction({required IconData icon, required String label, required VoidCallback onTap, bool active = false}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: active ? AppColors.light : AppColors.soft,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: active ? AppColors.primary.withValues(alpha: 0.35) : AppColors.border),
        ),
        child: Row(
          children: [
            Icon(icon, size: 17, color: active ? AppColors.primary : AppColors.navy),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: active ? AppColors.primary : AppColors.navy)),
          ],
        ),
      ),
    );
  }
}
