import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shimmer/shimmer.dart';

import '../core/theme.dart';
import '../models/models.dart';
import '../screens/channel/channel_screen.dart';
import '../screens/shorts/shorts_screen.dart';
import '../screens/video/video_player_screen.dart';
import 'widgets.dart';

/// Full-width feed card following the familiar video-platform layout.
class FeedVideoCard extends StatelessWidget {
  final Video video;
  const FeedVideoCard({super.key, required this.video});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => VideoPlayerScreen(video: video))),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: AspectRatio(
                aspectRatio: 16 / 9,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    _thumb(),
                    Positioned(right: 8, bottom: 8, child: _badge(video.durationText)),
                    if (video.isFeatured) Positioned(left: 8, top: 8, child: _badge('Featured', gradient: true)),
                  ],
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 2, 16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ChannelAvatar(channel: video.channel, size: 38),
                const SizedBox(width: 11),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        video.title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w600, color: AppColors.navy, height: 1.32),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              video.channel?.name ?? 'IT TUBE',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 12, color: AppColors.muted),
                            ),
                          ),
                          if (video.channel?.isVerified ?? false) ...[
                            const SizedBox(width: 3),
                            const Icon(Icons.verified_rounded, size: 13, color: AppColors.primary),
                          ],
                          Text(
                            ' · ${formatCount(video.viewsCount)} views · ${timeAgo(video.publishedAt)}',
                            style: const TextStyle(fontSize: 12, color: AppColors.muted),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                _menu(context),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _thumb() {
    final url = video.thumbnailUrl;
    if (url == null || url.isEmpty) {
      return Container(
        color: AppColors.light,
        child: const Center(child: Icon(Icons.play_circle_outline_rounded, color: AppColors.primary, size: 38)),
      );
    }
    return CachedNetworkImage(
      imageUrl: url,
      fit: BoxFit.cover,
      placeholder: (_, __) => Container(color: AppColors.soft),
      errorWidget: (_, __, ___) => Container(color: AppColors.light),
    );
  }

  Widget _badge(String text, {bool gradient = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
      decoration: BoxDecoration(
        color: gradient ? null : Colors.black.withValues(alpha: 0.74),
        gradient: gradient ? AppGradients.violetBrand : null,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(text, style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.w700)),
    );
  }

  Widget _menu(BuildContext context) {
    return IconButton(
      visualDensity: VisualDensity.compact,
      icon: const Icon(Icons.more_vert_rounded, size: 19, color: AppColors.muted),
      onPressed: () => showModalBottomSheet<void>(
        context: context,
        backgroundColor: Colors.white,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
        builder: (sheet) => SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 8),
              Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2))),
              const SizedBox(height: 8),
              ListTile(
                leading: const Icon(Icons.link_rounded, color: AppColors.navy),
                title: const Text('Copy link'),
                onTap: () {
                  Clipboard.setData(ClipboardData(text: video.playbackUrl ?? ''));
                  Navigator.pop(sheet);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Link copied to clipboard.')));
                },
              ),
              ListTile(
                leading: const Icon(Icons.person_outline_rounded, color: AppColors.navy),
                title: const Text('Open channel'),
                onTap: () {
                  Navigator.pop(sheet);
                  final channel = video.channel;
                  if (channel != null) {
                    Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChannelScreen(handle: channel.handle)));
                  }
                },
              ),
              ListTile(
                leading: const Icon(Icons.play_circle_outline_rounded, color: AppColors.navy),
                title: const Text('Watch now'),
                onTap: () {
                  Navigator.pop(sheet);
                  Navigator.of(context).push(MaterialPageRoute(builder: (_) => VideoPlayerScreen(video: video)));
                },
              ),
              const SizedBox(height: 6),
            ],
          ),
        ),
      ),
    );
  }
}

/// 9:16 card used by the horizontal Shorts shelf.
class ShortsCard extends StatelessWidget {
  final Video short;
  const ShortsCard({super.key, required this.short});

  @override
  Widget build(BuildContext context) {
    final published = short.publishedAt == null ? null : DateTime.tryParse(short.publishedAt!);
    final isNew = published != null && published.isAfter(DateTime.now().subtract(const Duration(days: 4)));

    return GestureDetector(
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ShortsScreen())),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: AspectRatio(
          aspectRatio: 9 / 16,
          child: Stack(
            fit: StackFit.expand,
            children: [
              if (short.thumbnailUrl != null && short.thumbnailUrl!.isNotEmpty)
                CachedNetworkImage(imageUrl: short.thumbnailUrl!, fit: BoxFit.cover)
              else
                Container(color: AppColors.lavender),
              const DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Colors.transparent, Color(0xB3071B68)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    stops: [0.45, 1],
                  ),
                ),
              ),
              Positioned(
                top: 8,
                left: 8,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(6)),
                  child: const Text('Shorts', style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w800, letterSpacing: 0.4)),
                ),
              ),
              if (isNew)
                Positioned(
                  top: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: AppColors.cyan, borderRadius: BorderRadius.circular(5)),
                    child: const Text('NEW', style: TextStyle(color: AppColors.navy, fontSize: 8.5, fontWeight: FontWeight.w900)),
                  ),
                ),
              Positioned(
                left: 10,
                right: 10,
                bottom: 10,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      short.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700, height: 1.28),
                    ),
                    const SizedBox(height: 3),
                    Text('${formatCount(short.viewsCount)} views', style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 10.5)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// "Shorts" shelf with two cards visible at a time.
class ShortsRow extends StatelessWidget {
  final List<Video> shorts;
  const ShortsRow({super.key, required this.shorts});

  @override
  Widget build(BuildContext context) {
    if (shorts.isEmpty) return const SizedBox.shrink();

    final width = MediaQuery.of(context).size.width;
    final cardHeight = ((width - 42) / 2) * 16 / 9;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 8, 10),
          child: Row(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(9)),
                child: const Icon(Icons.bolt_rounded, size: 18, color: Colors.white),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text('Shorts', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.navy, letterSpacing: -0.3)),
              ),
              IconButton(
                onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ShortsScreen())),
                icon: const Icon(Icons.more_horiz_rounded, color: AppColors.muted),
              ),
            ],
          ),
        ),
        SizedBox(
          height: cardHeight.clamp(230.0, 340.0),
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: shorts.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (context, i) => SizedBox(
              width: cardHeight.clamp(230.0, 340.0) * 9 / 16,
              child: ShortsCard(short: shorts[i]),
            ),
          ),
        ),
      ],
    );
  }
}

class LiveCard extends StatelessWidget {
  final LiveStream stream;
  const LiveCard({super.key, required this.stream});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: AspectRatio(
              aspectRatio: 16 / 9,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (stream.thumbnailUrl != null && stream.thumbnailUrl!.isNotEmpty)
                    CachedNetworkImage(imageUrl: stream.thumbnailUrl!, fit: BoxFit.cover)
                  else
                    Container(decoration: const BoxDecoration(gradient: AppGradients.deep)),
                  Positioned(
                    left: 8,
                    top: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(color: AppColors.danger, borderRadius: BorderRadius.circular(6)),
                      child: Text(
                        stream.isLive ? 'LIVE' : 'SCHEDULED',
                        style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.w900, letterSpacing: 0.6),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(gradient: AppGradients.brand, shape: BoxShape.circle),
                alignment: Alignment.center,
                child: Text(
                  (stream.channel?.name.isNotEmpty ?? false) ? stream.channel!.name[0].toUpperCase() : 'I',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      stream.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.navy, height: 1.3),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '${stream.channel?.name ?? 'IT TUBE'} · ${stream.isLive ? 'Live now' : 'Scheduled'}',
                      style: const TextStyle(fontSize: 12, color: AppColors.muted),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class FeedSkeletonList extends StatelessWidget {
  final int count;
  const FeedSkeletonList({super.key, this.count = 3});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFEDF2F9),
      highlightColor: const Color(0xFFF8FBFF),
      child: Column(
        children: List.generate(
          count,
          (_) => Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AspectRatio(
                  aspectRatio: 16 / 9,
                  child: Container(decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16))),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Container(width: 38, height: 38, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle)),
                    const SizedBox(width: 11),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(height: 12, width: 240, color: Colors.white),
                          const SizedBox(height: 6),
                          Container(height: 10, width: 150, color: Colors.white),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
