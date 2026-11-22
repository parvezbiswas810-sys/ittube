import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../search/search_screen.dart';

class ExploreScreen extends StatefulWidget {
  const ExploreScreen({super.key});

  @override
  State<ExploreScreen> createState() => _ExploreScreenState();
}

class _ExploreScreenState extends State<ExploreScreen> {
  List<Category> _categories = [];
  List<Video> _trending = [];
  List<Channel> _channels = [];
  bool _loading = true;
  String? _error;
  String? _selectedCategory;

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
    final api = context.read<ApiService>();
    try {
      final results = await Future.wait([
        api.categories(),
        api.videos(sort: 'trending'),
        api.channels(featured: true),
      ]);
      if (!mounted) return;
      setState(() {
        _categories = results[0] as List<Category>;
        _trending = (results[1] as (List<Video>, Pagination)).$1;
        _channels = results[2] as List<Channel>;
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

  Future<void> _loadCategory(String slug) async {
    setState(() {
      _selectedCategory = slug;
      _loading = true;
    });
    try {
      final (videos, _) = await context.read<ApiService>().videos(category: slug, sort: 'popular');
      if (!mounted) return;
      setState(() {
        _trending = videos;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Explore'),
        actions: [
          IconButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SearchScreen())), icon: const Icon(Icons.search_rounded)),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.only(bottom: 30),
          children: [
            SizedBox(
              height: 44,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                itemCount: _categories.length + 1,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (context, i) {
                  if (i == 0) {
                    final selected = _selectedCategory == null;
                    return ChoiceChip(
                      label: const Text('Trending'),
                      selected: selected,
                      onSelected: (_) {
                        setState(() => _selectedCategory = null);
                        _load();
                      },
                    );
                  }
                  final category = _categories[i - 1];
                  return ChoiceChip(
                    label: Text(category.name),
                    selected: _selectedCategory == category.slug,
                    onSelected: (_) => _loadCategory(category.slug),
                  );
                },
              ),
            ),
            if (_loading)
              const Padding(padding: EdgeInsets.only(top: 30), child: Center(child: CircularProgressIndicator()))
            else if (_error != null)
              SizedBox(height: 300, child: ErrorState(message: _error!, onRetry: _load))
            else ...[
              const SectionHeader(title: 'Popular creators', icon: Icons.people_alt_rounded),
              if (_channels.isEmpty)
                const Padding(padding: EdgeInsets.all(16), child: Text('No creators yet.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)))
              else
                ..._channels.take(5).map((c) => ChannelTile(channel: c)),
              const SectionHeader(title: 'Trending videos', icon: Icons.local_fire_department_rounded),
              if (_trending.isEmpty)
                const Padding(padding: EdgeInsets.all(16), child: Text('No videos in this category yet.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)))
              else
                ..._trending.map((v) => VideoCard(video: v, compact: true)),
            ],
          ],
        ),
      ),
    );
  }
}
