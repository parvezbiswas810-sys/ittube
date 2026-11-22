import 'dart:io';

import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:video_player/video_player.dart';

import '../../core/theme.dart';
import '../../widgets/widgets.dart';

class DownloadsScreen extends StatefulWidget {
  const DownloadsScreen({super.key});

  @override
  State<DownloadsScreen> createState() => _DownloadsScreenState();
}

class _DownloadsScreenState extends State<DownloadsScreen> {
  List<FileSystemEntity> _files = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<Directory> _folder() async {
    final dir = await getApplicationDocumentsDirectory();
    return Directory('${dir.path}/IT TUBE');
  }

  Future<void> _load() async {
    final folder = await _folder();
    final files = await folder.exists() ? await folder.list().where((f) => f is File).toList() : <FileSystemEntity>[];
    if (!mounted) return;
    setState(() {
      _files = files;
      _loading = false;
    });
  }

  Future<void> _delete(File file) async {
    await file.delete();
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Downloads')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _files.isEmpty
              ? const EmptyState(
                  title: 'No downloads yet',
                  message: 'Videos you download are saved here and can be watched offline.',
                  icon: Icons.download_outlined,
                )
              : ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  itemCount: _files.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final file = _files[index] as File;
                    final name = file.uri.pathSegments.last;
                    final size = file.lengthSync();
                    return Container(
                      decoration: BoxDecoration(color: AppColors.ice, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
                      child: ListTile(
                        leading: const Icon(Icons.play_circle_outline_rounded, color: AppColors.primary),
                        title: Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.navy)),
                        subtitle: Text('${(size / 1048576).toStringAsFixed(1)} MB', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                        trailing: IconButton(
                          onPressed: () => _delete(file),
                          icon: const Icon(Icons.delete_outline_rounded, color: AppColors.danger),
                        ),
                        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => _OfflinePlayer(file: file, title: name))),
                      ),
                    );
                  },
                ),
    );
  }
}

class _OfflinePlayer extends StatefulWidget {
  final File file;
  final String title;
  const _OfflinePlayer({required this.file, required this.title});

  @override
  State<_OfflinePlayer> createState() => _OfflinePlayerState();
}

class _OfflinePlayerState extends State<_OfflinePlayer> {
  late final VideoPlayerController _controller = VideoPlayerController.file(widget.file);

  @override
  void initState() {
    super.initState();
    _controller.initialize().then((_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, maxLines: 1, overflow: TextOverflow.ellipsis)),
      backgroundColor: Colors.black,
      body: Center(
        child: _controller.value.isInitialized
            ? AspectRatio(
                aspectRatio: _controller.value.aspectRatio,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    VideoPlayer(_controller),
                    GestureDetector(
                      onTap: () => setState(() => _controller.value.isPlaying ? _controller.pause() : _controller.play()),
                      child: Container(
                        width: 64,
                        height: 64,
                        decoration: const BoxDecoration(color: Colors.black45, shape: BoxShape.circle),
                        child: Icon(_controller.value.isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded, color: Colors.white, size: 36),
                      ),
                    ),
                  ],
                ),
              )
            : const CircularProgressIndicator(color: Colors.white),
      ),
    );
  }
}
