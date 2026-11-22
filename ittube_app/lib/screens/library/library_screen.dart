import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../auth/login_screen.dart';
import '../video/video_player_screen.dart';

class LibraryScreen extends StatefulWidget {
  const LibraryScreen({super.key});

  @override
  State<LibraryScreen> createState() => _LibraryScreenState();
}

class _LibraryScreenState extends State<LibraryScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabs = TabController(length: 3, vsync: this);
  List<HistoryItem> _history = [];
  List<Playlist> _playlists = [];
  List<Video> _uploads = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      setState(() => _loading = false);
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = context.read<ApiService>();
    try {
      final results = await Future.wait([api.history(), api.playlists(), api.myUploads()]);
      if (!mounted) return;
      setState(() {
        _history = (results[0] as (List<HistoryItem>, Pagination)).$1;
        _playlists = results[1] as List<Playlist>;
        _uploads = (results[2] as (List<Video>, Pagination)).$1;
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

  Future<void> _clearHistory() async {
    await context.read<ApiService>().clearHistory();
    setState(() => _history = []);
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    if (!auth.isAuthenticated) {
      return Scaffold(
        appBar: AppBar(title: const Text('Library')),
        body: EmptyState(
          title: 'Your library lives here',
          message: 'Sign in to keep watch history, playlists and your uploads in one place.',
          icon: Icons.video_library_rounded,
          action: FilledButton(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
            style: FilledButton.styleFrom(minimumSize: const Size(170, 46)),
            child: const Text('Sign in'),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Library'),
        bottom: TabBar(controller: _tabs, tabs: const [Tab(text: 'History'), Tab(text: 'Playlists'), Tab(text: 'Uploads')]),
        actions: [
          if (_history.isNotEmpty)
            IconButton(onPressed: _clearHistory, tooltip: 'Clear history', icon: const Icon(Icons.delete_sweep_outlined)),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : TabBarView(
                  controller: _tabs,
                  children: [
                    RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: _load,
                      child: _history.isEmpty
                          ? ListView(children: const [SizedBox(height: 120), EmptyState(title: 'No watch history', message: 'Videos you watch will appear here.', icon: Icons.history_rounded)])
                          : ListView.builder(
                              padding: const EdgeInsets.only(bottom: 24),
                              itemCount: _history.length,
                              itemBuilder: (context, i) => VideoCard(video: _history[i].video, compact: true),
                            ),
                    ),
                    RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: _load,
                      child: _playlists.isEmpty
                          ? ListView(children: const [SizedBox(height: 120), EmptyState(title: 'No playlists', message: 'Create playlists to organise your favourite videos.', icon: Icons.queue_music_rounded)])
                          : ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _playlists.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 10),
                              itemBuilder: (context, i) {
                                final playlist = _playlists[i];
                                return Container(
                                  padding: const EdgeInsets.all(14),
                                  decoration: BoxDecoration(color: AppColors.ice, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
                                  child: Row(
                                    children: [
                                      Container(
                                        width: 48,
                                        height: 48,
                                        decoration: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(12)),
                                        child: const Icon(Icons.playlist_play_rounded, color: AppColors.primary),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(playlist.title, style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
                                            Text('${playlist.videosCount} videos · ${playlist.visibility}', style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
                                          ],
                                        ),
                                      ),
                                      const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
                                    ],
                                  ),
                                );
                              },
                            ),
                    ),
                    RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: _load,
                      child: _uploads.isEmpty
                          ? ListView(children: const [SizedBox(height: 120), EmptyState(title: 'No uploads yet', message: 'Upload your first video with the + button on Home.', icon: Icons.cloud_upload_outlined)])
                          : ListView.builder(
                              padding: const EdgeInsets.only(bottom: 24),
                              itemCount: _uploads.length,
                              itemBuilder: (context, i) {
                                final video = _uploads[i];
                                return Column(
                                  children: [
                                    VideoCard(video: video, compact: true),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 16),
                                      child: Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                            decoration: BoxDecoration(color: video.status == 'published' ? const Color(0xFFE7F8F1) : const Color(0xFFFEF6E7), borderRadius: BorderRadius.circular(6)),
                                            child: Text(video.status, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: video.status == 'published' ? AppColors.success : const Color(0xFFB45309))),
                                          ),
                                          const Spacer(),
                                          TextButton(
                                            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VideoPlayerScreen(video: video))),
                                            child: const Text('Open', style: TextStyle(fontSize: 12)),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const Divider(height: 8),
                                  ],
                                );
                              },
                            ),
                    ),
                  ],
                ),
    );
  }
}
