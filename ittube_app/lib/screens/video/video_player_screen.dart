import 'dart:io';

import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';

import '../../core/api_client.dart';
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
  const VideoPlayerScreen({super.key, required this.video});

  @override
  State<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends State<VideoPlayerScreen> {
  late Video _video = widget.video;
  VideoPlayerController? _controller;
  bool _ready = false;
  bool _controls = true;
  bool _fullscreen = false;
  bool _subscribed = false;
  List<Video> _related = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _subscribed = _video.channel?.isSubscribed ?? false;
    _init();
    _loadRelated();
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
      await controller.play();
      if (!mounted) return;
      setState(() => _ready = true);
      controller.addListener(() {
        if (mounted) setState(() {});
      });
      context.read<ApiService>().recordView(_video.id).catchError((_) => <String, dynamic>{});
    } catch (e) {
      if (mounted) setState(() => _error = 'Unable to play this video. Please try again.');
    }
  }

  Future<void> _loadRelated() async {
    try {
      final related = await context.read<ApiService>().related(_video.id);
      if (mounted) setState(() => _related = related);
    } catch (_) {}
  }

  @override
  void dispose() {
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

  void _share() {
    final link = _video.playbackUrl ?? '';
    Clipboard.setData(ClipboardData(text: link));
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Video link copied to clipboard.')));
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
            const SectionHeader(title: 'Up next', icon: Icons.playlist_play_rounded),
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
          child: Center(child: Text(_error!, style: const TextStyle(color: Colors.white70, fontSize: 12.5))),
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
              if (_video.thumbnailUrl != null) Image.network(_video.thumbnailUrl!, fit: BoxFit.cover),
              const Center(child: CircularProgressIndicator(color: Colors.white)),
            ],
          ),
        ),
      );
    }

    final controller = _controller!;
    return AspectRatio(
      aspectRatio: controller.value.aspectRatio == 0 ? 16 / 9 : controller.value.aspectRatio,
      child: GestureDetector(
        onTap: () => setState(() => _controls = !_controls),
        child: Stack(
          fit: StackFit.expand,
          children: [
            Center(child: VideoPlayer(controller)),
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
                            controller.value.isPlaying ? controller.pause() : controller.play();
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
