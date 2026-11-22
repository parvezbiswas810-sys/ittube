import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/widgets.dart';
import '../notifications/notifications_screen.dart';
import '../profile/profile_screen.dart';
import '../search/search_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final feed = context.watch<FeedProvider>();
    final auth = context.watch<AuthProvider>();
    final config = context.watch<ConfigProvider>().config;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            const AppLogo(size: 32),
            const SizedBox(width: 9),
            const Text('IT TUBE', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: 0.6, color: AppColors.navy)),
          ],
        ),
        actions: [
          IconButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SearchScreen())), icon: const Icon(Icons.search_rounded)),
          IconButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const NotificationsScreen())), icon: const Icon(Icons.notifications_none_rounded)),
          Padding(
            padding: const EdgeInsets.only(right: 12, left: 4),
            child: GestureDetector(
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ProfileScreen())),
              child: Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [AppColors.primary, AppColors.cyan]),
                  borderRadius: BorderRadius.circular(11),
                ),
                alignment: Alignment.center,
                child: Text(
                  auth.user != null ? auth.user!.username[0].toUpperCase() : 'I',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14),
                ),
              ),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () => feed.load(refresh: true),
        child: _body(context, feed, config.announcement),
      ),
    );
  }

  Widget _body(BuildContext context, FeedProvider feed, String? announcement) {
    if (feed.loading && feed.sections.isEmpty) {
      return ListView(children: const [SkeletonFeed()]);
    }
    if (feed.error != null && feed.sections.isEmpty) {
      return ListView(
        children: [SizedBox(height: 380, child: ErrorState(message: feed.error!, onRetry: () => feed.load(refresh: true)))],
      );
    }

    final visibleSections = feed.sections.where((s) => s.videos.isNotEmpty).toList();

    if (visibleSections.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 160),
          EmptyState(
            title: 'No videos yet',
            message: 'Uploads from creators will appear here. Pull down to refresh.',
          ),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.only(bottom: 30),
      itemCount: visibleSections.length + (announcement != null ? 1 : 0),
      itemBuilder: (context, index) {
        if (announcement != null) {
          if (index == 0) {
            return Container(
              margin: const EdgeInsets.fromLTRB(16, 14, 16, 2),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [AppColors.navy, AppColors.primary]),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Row(
                children: [
                  const Icon(Icons.campaign_rounded, color: Colors.white, size: 22),
                  const SizedBox(width: 10),
                  Expanded(child: Text(announcement, style: const TextStyle(color: Colors.white, fontSize: 12.5, height: 1.4))),
                ],
              ),
            );
          }
          index -= 1;
        }

        final section = visibleSections[index];
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SectionHeader(
              title: section.title,
              icon: section.type == 'shorts' ? Icons.bolt_rounded : (section.type == 'trending' ? Icons.local_fire_department_rounded : Icons.play_circle_fill_rounded),
              onSeeAll: section.type == 'shorts'
                  ? null
                  : () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => SearchScreen(initialQuery: section.title))),
            ),
            SizedBox(
              height: 252,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                itemCount: section.videos.length,
                separatorBuilder: (_, __) => const SizedBox(width: 14),
                itemBuilder: (context, i) => SizedBox(width: 260, child: VideoCard(video: section.videos[i])),
              ),
            ),
          ],
        );
      },
    );
  }
}
