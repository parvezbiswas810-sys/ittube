import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../auth/login_screen.dart';

class SubscriptionsScreen extends StatefulWidget {
  const SubscriptionsScreen({super.key});

  @override
  State<SubscriptionsScreen> createState() => _SubscriptionsScreenState();
}

class _SubscriptionsScreenState extends State<SubscriptionsScreen> {
  List<Video> _videos = [];
  List<Channel> _channels = [];
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
      final results = await Future.wait([api.subscriptionFeed(), api.subscriptions()]);
      if (!mounted) return;
      setState(() {
        _videos = (results[0] as (List<Video>, Pagination)).$1;
        _channels = results[1] as List<Channel>;
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
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    if (!auth.isAuthenticated) {
      return Scaffold(
        appBar: AppBar(title: const Text('Subscriptions')),
        body: EmptyState(
          title: 'Sign in to see subscriptions',
          message: 'Subscribe to creators and their latest uploads will appear here.',
          icon: Icons.subscriptions_rounded,
          action: FilledButton(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
            style: FilledButton.styleFrom(minimumSize: const Size(170, 46)),
            child: const Text('Sign in'),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Subscriptions')),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _load,
        child: _loading
            ? const SkeletonFeed()
            : _error != null
                ? ErrorState(message: _error!, onRetry: _load)
                : ListView(
                    padding: const EdgeInsets.only(bottom: 30),
                    children: [
                      if (_channels.isNotEmpty) ...[
                        const SectionHeader(title: 'Your channels', icon: Icons.people_alt_rounded),
                        SizedBox(
                          height: 92,
                          child: ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            scrollDirection: Axis.horizontal,
                            itemCount: _channels.length,
                            separatorBuilder: (_, __) => const SizedBox(width: 18),
                            itemBuilder: (context, i) {
                              final c = _channels[i];
                              return SizedBox(
                                width: 64,
                                child: Column(
                                  children: [
                                    ChannelAvatar(channel: c, size: 54),
                                    const SizedBox(height: 6),
                                    Text(c.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10.5, color: AppColors.muted)),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ],
                      const SectionHeader(title: 'Latest from your channels', icon: Icons.play_circle_fill_rounded),
                      if (_videos.isEmpty)
                        const Padding(padding: EdgeInsets.all(24), child: Text('No uploads from your subscriptions yet.', style: TextStyle(color: AppColors.muted, fontSize: 12.5)))
                      else
                        ..._videos.map((v) => VideoCard(video: v, compact: true)),
                    ],
                  ),
      ),
    );
  }
}
