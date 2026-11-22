import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';

class SearchScreen extends StatefulWidget {
  final String? initialQuery;
  const SearchScreen({super.key, this.initialQuery});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _controller = TextEditingController();
  List<Video> _videos = [];
  List<Channel> _channels = [];
  List<String> _history = [];
  bool _loading = false;
  bool _searched = false;
  String? _error;
  String _filter = 'all';

  @override
  void initState() {
    super.initState();
    _loadHistory();
    if (widget.initialQuery != null && widget.initialQuery!.trim().isNotEmpty) {
      _controller.text = widget.initialQuery!;
      WidgetsBinding.instance.addPostFrameCallback((_) => _search());
    }
  }

  Future<void> _loadHistory() async {
    final prefs = await SharedPreferences.getInstance();
    if (!mounted) return;
    setState(() => _history = prefs.getStringList('ittube_search_history') ?? []);
  }

  Future<void> _saveHistory(String term) async {
    final prefs = await SharedPreferences.getInstance();
    final list = prefs.getStringList('ittube_search_history') ?? [];
    list.remove(term);
    list.insert(0, term);
    await prefs.setStringList('ittube_search_history', list.take(10).toList());
    if (mounted) setState(() => _history = list.take(10).toList());
  }

  Future<void> _clearHistory() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('ittube_search_history');
    if (mounted) setState(() => _history = []);
  }

  Future<void> _search({String? term}) async {
    final query = (term ?? _controller.text).trim();
    if (query.isEmpty) return;
    _controller.text = query;
    setState(() {
      _loading = true;
      _searched = true;
      _error = null;
    });
    try {
      final json = await context.read<ApiService>().search(query, filter: _filter);
      final data = json['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() {
        _videos = (data['videos'] as List? ?? []).whereType<Map<String, dynamic>>().map(Video.fromJson).toList();
        _channels = (data['channels'] as List? ?? []).whereType<Map<String, dynamic>>().map(Channel.fromJson).toList();
        _loading = false;
      });
      await _saveHistory(query);
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
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: TextField(
          controller: _controller,
          autofocus: widget.initialQuery == null,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _search(),
          decoration: const InputDecoration(
            hintText: 'Search videos, creators, categories',
            prefixIcon: Icon(Icons.search_rounded, size: 20),
            filled: false,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
            border: InputBorder.none,
            contentPadding: EdgeInsets.zero,
          ),
        ),
        actions: [
          TextButton(onPressed: () => _search(), child: const Text('Search')),
        ],
      ),
      body: Column(
        children: [
          SizedBox(
            height: 44,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                for (final f in ['all', 'videos', 'channels', 'shorts'])
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(f[0].toUpperCase() + f.substring(1)),
                      selected: _filter == f,
                      onSelected: (_) {
                        setState(() => _filter = f);
                        if (_searched) _search();
                      },
                    ),
                  ),
              ],
            ),
          ),
          Expanded(child: _results()),
        ],
      ),
    );
  }

  Widget _results() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return ErrorState(message: _error!, onRetry: _search);
    if (!_searched) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              const Text('Recent searches', style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
              const Spacer(),
              if (_history.isNotEmpty) TextButton(onPressed: _clearHistory, child: const Text('Clear', style: TextStyle(fontSize: 12))),
            ],
          ),
          if (_history.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(vertical: 26), child: Text('Search for videos, creators or categories.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)))
          else
            ..._history.map((h) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.history_rounded, color: AppColors.muted, size: 20),
                  title: Text(h, style: const TextStyle(fontSize: 13.5)),
                  onTap: () => _search(term: h),
                )),
        ],
      );
    }

    if (_videos.isEmpty && _channels.isEmpty) {
      return const EmptyState(title: 'No results', message: 'Try different keywords or browse Explore.', icon: Icons.search_off_rounded);
    }

    return ListView(
      padding: const EdgeInsets.only(bottom: 30),
      children: [
        if (_channels.isNotEmpty) ...[
          const SectionHeader(title: 'Channels', icon: Icons.people_alt_rounded),
          ..._channels.map((c) => ChannelTile(channel: c)),
        ],
        if (_videos.isNotEmpty) ...[
          const SectionHeader(title: 'Videos', icon: Icons.play_circle_fill_rounded),
          ..._videos.map((v) => VideoCard(video: v, compact: true)),
        ],
      ],
    );
  }
}
