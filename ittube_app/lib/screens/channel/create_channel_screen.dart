import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../models/models.dart';
import '../../services/api_service.dart';

class CreateChannelScreen extends StatefulWidget {
  const CreateChannelScreen({super.key});

  @override
  State<CreateChannelScreen> createState() => _CreateChannelScreenState();
}

class _CreateChannelScreenState extends State<CreateChannelScreen> {
  final _name = TextEditingController();
  final _handle = TextEditingController();
  final _description = TextEditingController();
  final _picker = ImagePicker();
  File? _avatar;
  File? _banner;
  int? _categoryId;
  List<Category> _categories = const [];
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    try {
      final categories = await context.read<ApiService>().categories();
      if (mounted) setState(() => _categories = categories);
    } catch (_) {}
  }

  Future<void> _pick(bool banner) async {
    final picked = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 88, maxWidth: banner ? 2000 : 800);
    if (picked == null) return;
    setState(() {
      if (banner) {
        _banner = File(picked.path);
      } else {
        _avatar = File(picked.path);
      }
    });
  }

  Future<void> _save() async {
    final name = _name.text.trim();
    final handle = _handle.text.trim();
    if (name.length < 3) {
      setState(() => _error = 'Channel name must be at least 3 characters.');
      return;
    }
    if (!RegExp(r'^[a-zA-Z0-9_.]{3,30}$').hasMatch(handle)) {
      setState(() => _error = 'Handle must be 3-30 characters: letters, numbers, dot or underscore.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await context.read<ApiService>().createChannel(
            name: name,
            handle: handle.toLowerCase(),
            description: _description.text.trim().isEmpty ? null : _description.text.trim(),
            categoryId: _categoryId,
            avatar: _avatar,
            banner: _banner,
          );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      setState(() => _error = e.errors.isNotEmpty ? e.errors.values.first.toString() : e.message);
    } catch (_) {
      setState(() => _error = 'Could not create the channel. Try again.');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _handle.dispose();
    _description.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create your channel')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 30),
        children: [
          const Text(
            'Your channel is how viewers find your videos. You can change everything later.',
            style: TextStyle(fontSize: 12.5, color: AppColors.muted, height: 1.5),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              GestureDetector(
                onTap: _saving ? null : () => _pick(false),
                child: Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    color: AppColors.ice,
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.border),
                    image: _avatar == null ? null : DecorationImage(image: FileImage(_avatar!), fit: BoxFit.cover),
                  ),
                  child: _avatar == null ? const Icon(Icons.add_a_photo_outlined, color: AppColors.primary) : null,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _saving ? null : () => _pick(true),
                  icon: const Icon(Icons.panorama_outlined, size: 18),
                  label: Text(_banner == null ? 'Add banner' : 'Change banner'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _name,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(labelText: 'Channel name'),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _handle,
            decoration: const InputDecoration(labelText: 'Handle', prefixText: '@'),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _description,
            maxLines: 3,
            decoration: const InputDecoration(labelText: 'Description (optional)'),
          ),
          const SizedBox(height: 14),
          DropdownButtonFormField<int>(
            initialValue: _categoryId,
            decoration: const InputDecoration(labelText: 'Category'),
            items: [
              for (final category in _categories) DropdownMenuItem(value: category.id, child: Text(category.name)),
            ],
            onChanged: (value) => setState(() => _categoryId = value),
          ),
          if (_error != null) ...[
            const SizedBox(height: 16),
            Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5)),
          ],
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: _saving
                ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                : const Text('Create channel'),
          ),
        ],
      ),
    );
  }
}
