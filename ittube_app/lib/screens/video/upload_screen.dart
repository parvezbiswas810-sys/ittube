import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/config_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../channel/create_channel_screen.dart';

class UploadScreen extends StatefulWidget {
  const UploadScreen({super.key});

  @override
  State<UploadScreen> createState() => _UploadScreenState();
}

class _UploadScreenState extends State<UploadScreen> {
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _tags = TextEditingController();
  File? _video;
  File? _thumbnail;
  int? _categoryId;
  String _visibility = 'public';
  String _type = 'video';
  List<Category> _categories = [];
  bool _uploading = false;
  double _progress = 0;
  String? _error;
  String _stage = '';
  bool _cancelRequested = false;
  UploadLimits? _limits;
  bool _checkingChannel = true;
  bool _hasChannel = false;

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadLimits();
    _checkChannel();
  }

  Future<void> _checkChannel() async {
    var has = false;
    try {
      await context.read<ApiService>().myChannel();
      has = true;
    } catch (_) {}
    if (mounted) {
      setState(() {
        _hasChannel = has;
        _checkingChannel = false;
      });
    }
  }

  Future<void> _openChannelWizard() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const CreateChannelScreen()),
    );
    if (created == true) _checkChannel();
  }

  Future<void> _loadLimits() async {
    try {
      final limits = await context.read<ApiService>().uploadLimits();
      if (mounted) setState(() => _limits = limits);
    } catch (_) {}
  }

  Future<void> _loadCategories() async {
    try {
      final categories = await context.read<ApiService>().categories();
      if (mounted) setState(() => _categories = categories);
    } catch (_) {}
  }

  Future<void> _pickVideo() async {
    final picked = await FilePicker.pickFile(type: FileType.video);
    if (picked != null && picked.path != null) {
      setState(() => _video = File(picked.path!));
    }
  }

  Future<void> _pickThumbnail() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 88, maxWidth: 1600);
    if (picked != null) setState(() => _thumbnail = File(picked.path));
  }

  Future<void> _upload() async {
    if (_video == null) {
      setState(() => _error = 'Choose a video file first.');
      return;
    }
    if (_title.text.trim().length < 3) {
      setState(() => _error = 'Give your video a title (3+ characters).');
      return;
    }

    final service = context.read<ApiService>();
    final size = await _video!.length();
    final limits = _limits;
    final maxMb = limits?.maxUploadMb ?? 512;

    if (size > maxMb * 1024 * 1024) {
      setState(() => _error = 'This file is ${(size / 1048576).toStringAsFixed(1)} MB. The limit is $maxMb MB.');
      return;
    }

    setState(() {
      _uploading = true;
      _progress = 0;
      _stage = 'Preparing…';
      _cancelRequested = false;
      _error = null;
    });

    final useChunks = limits == null || !limits.canUploadDirectly(size);

    try {
      final Video video;
      if (useChunks) {
        video = await service.uploadVideoResumable(
          file: _video!,
          title: _title.text.trim(),
          description: _description.text.trim().isEmpty ? null : _description.text.trim(),
          categoryId: _categoryId,
          visibility: _visibility,
          type: _type,
          tags: _tags.text.trim().isEmpty ? null : _tags.text.trim(),
          thumbnail: _thumbnail,
          limits: limits,
          isCancelled: () => _cancelRequested,
          onProgress: (p, stage) {
            if (mounted) setState(() {
              _progress = p;
              _stage = stage;
            });
          },
        );
      } else {
        video = await service.uploadVideo(
          file: _video!,
          title: _title.text.trim(),
          description: _description.text.trim().isEmpty ? null : _description.text.trim(),
          categoryId: _categoryId,
          visibility: _visibility,
          type: _type,
          tags: _tags.text.trim().isEmpty ? null : _tags.text.trim(),
          thumbnail: _thumbnail,
          onProgress: (p) {
            if (mounted) setState(() {
              _progress = p.clamp(0.0, 0.99);
              _stage = 'Uploading ${(p * 100).toStringAsFixed(0)}%';
            });
          },
        );
      }

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(video.status == 'pending' ? 'Upload complete. Waiting for review.' : 'Upload complete. Your video is live.'),
      ));
      Navigator.of(context).pop(true);
    } on UploadCancelledException {
      if (mounted) setState(() => _error = 'Upload cancelled.');
    } on ApiException catch (e) {
      final detail = e.errors.values
          .expand((v) => v is List ? v.map((x) => x.toString()) : [v.toString()])
          .firstOrNull;
      if (mounted) setState(() => _error = detail == null || e.message.contains(detail) ? e.message : '${e.message}\n$detail');
    } catch (_) {
      if (mounted) setState(() => _error = 'Upload failed. Check your connection and try again.');
    } finally {
      if (mounted) setState(() {
        _uploading = false;
        _stage = '';
      });
    }
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _tags.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final config = context.watch<ConfigProvider>().config;

    return Scaffold(
      appBar: AppBar(title: const Text('Upload')),
      body: !config.uploadsEnabled
          ? const EmptyState(title: 'Uploads disabled', message: 'Video uploads are currently disabled by the administrator.', icon: Icons.cloud_off_rounded)
          : _checkingChannel
              ? const Center(child: CircularProgressIndicator())
              : !_hasChannel
                  ? Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const AppLogo(size: 72),
                          const SizedBox(height: 18),
                          const Text('Create your channel first', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.navy)),
                          const SizedBox(height: 8),
                          const Text(
                            'Videos are published on your channel. It only takes a moment.',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 12.5, color: AppColors.muted, height: 1.5),
                          ),
                          const SizedBox(height: 20),
                          FilledButton(onPressed: _openChannelWizard, child: const Text('Create channel')),
                        ],
                      ),
                    )
                  : ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 30),
              children: [
                GestureDetector(
                  onTap: _uploading ? null : _pickVideo,
                  child: Container(
                    height: 180,
                    decoration: BoxDecoration(
                      color: AppColors.ice,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: _video == null ? AppColors.border : AppColors.primary, width: _video == null ? 1 : 1.6),
                    ),
                    child: _video == null
                        ? Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 56,
                                height: 56,
                                decoration: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(16)),
                                child: const Icon(Icons.video_call_rounded, color: AppColors.primary, size: 28),
                              ),
                              const SizedBox(height: 12),
                              const Text('Select video file', style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
                              const SizedBox(height: 4),
                              Text(
                                'Up to ${_limits?.maxUploadMb ?? config.maxUploadSizeMb} MB · ${(_limits?.allowedFormats.isNotEmpty == true ? _limits!.allowedFormats : config.allowedFormats).join(', ').toUpperCase()}',
                                style: const TextStyle(fontSize: 11, color: AppColors.muted),
                              ),
                            ],
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.check_circle_rounded, color: AppColors.success, size: 40),
                              const SizedBox(height: 8),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 18),
                                child: Text(_video!.path.split('/').last, textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5, color: AppColors.navy, fontWeight: FontWeight.w600)),
                              ),
                              const SizedBox(height: 6),
                              Text('${(_video!.lengthSync() / 1048576).toStringAsFixed(1)} MB', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                            ],
                          ),
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _uploading ? null : _pickThumbnail,
                        icon: const Icon(Icons.image_outlined, size: 18),
                        label: Text(_thumbnail == null ? 'Thumbnail' : 'Change thumbnail'),
                        style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(46)),
                      ),
                    ),
                    if (_thumbnail != null) ...[
                      const SizedBox(width: 10),
                      ClipRRect(borderRadius: BorderRadius.circular(10), child: Image.file(_thumbnail!, width: 62, height: 46, fit: BoxFit.cover)),
                    ],
                  ],
                ),
                const SizedBox(height: 16),
                if (_error != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(12)),
                    child: Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5)),
                  ),
                  const SizedBox(height: 14),
                ],
                TextField(controller: _title, maxLength: 180, decoration: const InputDecoration(labelText: 'Title', counterText: '')),
                const SizedBox(height: 12),
                TextField(controller: _description, maxLines: 4, decoration: const InputDecoration(labelText: 'Description')),
                const SizedBox(height: 12),
                TextField(controller: _tags, decoration: const InputDecoration(labelText: 'Tags (comma separated)', prefixIcon: Icon(Icons.tag_rounded, size: 18))),
                const SizedBox(height: 12),
                DropdownButtonFormField<int>(
                  initialValue: _categoryId,
                  decoration: const InputDecoration(labelText: 'Category'),
                  items: _categories.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))).toList(),
                  onChanged: (v) => setState(() => _categoryId = v),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _visibility,
                        decoration: const InputDecoration(labelText: 'Visibility'),
                        items: const [
                          DropdownMenuItem(value: 'public', child: Text('Public')),
                          DropdownMenuItem(value: 'unlisted', child: Text('Unlisted')),
                          DropdownMenuItem(value: 'private', child: Text('Private')),
                        ],
                        onChanged: (v) => setState(() => _visibility = v ?? 'public'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _type,
                        decoration: const InputDecoration(labelText: 'Format'),
                        items: const [
                          DropdownMenuItem(value: 'video', child: Text('Video')),
                          DropdownMenuItem(value: 'short', child: Text('Short')),
                        ],
                        onChanged: (v) => setState(() => _type = v ?? 'video'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                if (_uploading) ...[
                  ClipRRect(
                    borderRadius: BorderRadius.circular(99),
                    child: LinearProgressIndicator(value: _progress, minHeight: 8, backgroundColor: AppColors.light, color: AppColors.primary),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _stage.isEmpty ? '${(_progress * 100).toStringAsFixed(0)}%' : _stage,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 12, color: AppColors.muted),
                  ),
                  TextButton.icon(
                    onPressed: _cancelRequested ? null : () => setState(() => _cancelRequested = true),
                    icon: const Icon(Icons.close_rounded, size: 16),
                    label: const Text('Cancel upload'),
                  ),
                  const SizedBox(height: 6),
                ],
                FilledButton(
                  onPressed: _uploading ? null : _upload,
                  child: Text(_uploading ? 'Uploading…' : 'Publish video'),
                ),
                const SizedBox(height: 10),
                const Text(
                  'By uploading you confirm you own the rights to this content and agree to the IT TUBE terms.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 10.5, color: AppColors.muted, height: 1.5),
                ),
              ],
            ),
    );
  }
}
