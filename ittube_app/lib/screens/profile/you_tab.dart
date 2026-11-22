import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../widgets/auth_gate.dart';
import '../../widgets/widgets.dart';
import '../channel/channel_screen.dart';
import '../channel/create_channel_screen.dart';
import '../downloads/downloads_screen.dart';
import '../library/library_screen.dart';
import '../notifications/notifications_screen.dart';
import 'profile_screen.dart';

/// The "You" tab: account summary, channel access and settings shortcuts.
class YouTab extends StatelessWidget {
  const YouTab({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final config = context.watch<ConfigProvider>().config;

    if (!auth.isAuthenticated) {
      return ListView(
        padding: const EdgeInsets.fromLTRB(20, 30, 20, 30),
        children: [
          Center(
            child: Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(30), boxShadow: AppShadows.glow),
              child: const AppLogo(size: 78),
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Sign in to IT TUBE',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800, color: AppColors.navy, letterSpacing: -0.3),
          ),
          const SizedBox(height: 8),
          const Text(
            'Like, comment, subscribe, upload and keep your history in sync across devices.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12.5, color: AppColors.muted, height: 1.5),
          ),
          const SizedBox(height: 22),
          FilledButton.icon(
            onPressed: () => ensureSignedIn(context, action: 'sign in'),
            style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
            icon: const Icon(Icons.g_mobiledata_rounded, size: 26),
            label: const Text('Continue with Google'),
          ),
          const SizedBox(height: 26),
          for (final (icon, text) in const [
            (Icons.history_rounded, 'Watch history and resume playback'),
            (Icons.playlist_play_rounded, 'Playlists and saved videos'),
            (Icons.upload_rounded, 'Upload videos and shorts'),
            (Icons.video_camera_front_rounded, 'Create your own channel'),
          ])
            Padding(
              padding: const EdgeInsets.only(bottom: 14),
              child: Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(color: AppColors.lavender, borderRadius: BorderRadius.circular(11)),
                    child: Icon(icon, size: 18, color: AppColors.violet),
                  ),
                  const SizedBox(width: 12),
                  Expanded(child: Text(text, style: const TextStyle(fontSize: 13, color: AppColors.navy, fontWeight: FontWeight.w500))),
                ],
              ),
            ),
          const SizedBox(height: 10),
          OutlinedButton(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ProfileScreen())),
            child: const Text('App settings & legal'),
          ),
        ],
      );
    }

    final user = auth.user;
    final channel = user?.channel;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 30),
      children: [
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(22), boxShadow: AppShadows.glow),
          child: Row(
            children: [
              ChannelAvatar(channel: channel, size: 54),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user?.displayName ?? user?.username ?? 'IT TUBE',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 2),
                    Text('@${user?.username ?? ''}', style: const TextStyle(color: Color(0xD9FFFFFF), fontSize: 12)),
                    if (user?.email != null) ...[
                      const SizedBox(height: 2),
                      Text(user!.email!, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xB3FFFFFF), fontSize: 11.5)),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: AppColors.border), boxShadow: AppShadows.card),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      channel == null ? 'No channel yet' : channel.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: AppColors.navy),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      channel == null ? 'Create a channel to upload videos' : '@${channel.handle} · ${formatCount(channel.subscribersCount)} subscribers',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11.5, color: AppColors.muted),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              FilledButton(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => channel == null ? const CreateChannelScreen() : ChannelScreen(handle: channel.handle)),
                ),
                style: FilledButton.styleFrom(minimumSize: const Size(96, 40)),
                child: Text(channel == null ? 'Create' : 'Open'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 22),
        const Text('Your library', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.navy)),
        const SizedBox(height: 10),
        _tile(context, Icons.history_rounded, 'History', 'Videos you watched', () => const LibraryScreen()),
        _tile(context, Icons.playlist_play_rounded, 'Playlists & saved', 'Your playlists and liked videos', () => const LibraryScreen()),
        _tile(context, Icons.download_done_rounded, 'Downloads', 'Available offline', () => const DownloadsScreen()),
        _tile(context, Icons.notifications_none_rounded, 'Notifications', 'Activity and announcements', () => const NotificationsScreen()),
        const SizedBox(height: 22),
        const Text('Settings', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.navy)),
        const SizedBox(height: 10),
        _tile(context, Icons.settings_outlined, 'Account & playback', 'Profile, privacy, password', () => const ProfileScreen()),
        _tile(context, Icons.description_outlined, 'Terms & privacy', 'Policies for using IT TUBE', null, onTap: () => _openLegal(context, config.termsUrl, config.privacyUrl)),
        _tile(context, Icons.help_outline_rounded, 'Help & support', 'Version ${config.version}', null, onTap: () => _showHelp(context, config.supportUrl)),
        const SizedBox(height: 22),
        OutlinedButton(
          onPressed: () async {
            await context.read<AuthProvider>().logout();
            if (!context.mounted) return;
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Signed out of IT TUBE.')));
          },
          style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48), foregroundColor: AppColors.danger, side: const BorderSide(color: Color(0xFFFBD5D5))),
          child: const Text('Sign out'),
        ),
      ],
    );
  }

  Widget _tile(BuildContext context, IconData icon, String title, String subtitle, Widget Function()? builder, {VoidCallback? onTap}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border), boxShadow: AppShadows.card),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        leading: Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(12)),
          child: Icon(icon, size: 19, color: AppColors.primary),
        ),
        title: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.navy)),
        subtitle: Text(subtitle, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
        onTap: onTap ?? (builder == null ? null : () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => builder()))),
      ),
    );
  }

  Future<void> _openLegal(BuildContext context, String? terms, String? privacy) async {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (sheet) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            ListTile(
              leading: const Icon(Icons.description_outlined, color: AppColors.navy),
              title: const Text('Terms of service'),
              onTap: () => terms == null ? null : launchUrl(Uri.parse(terms), mode: LaunchMode.externalApplication),
            ),
            ListTile(
              leading: const Icon(Icons.privacy_tip_outlined, color: AppColors.navy),
              title: const Text('Privacy policy'),
              onTap: () => privacy == null ? null : launchUrl(Uri.parse(privacy), mode: LaunchMode.externalApplication),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  void _showHelp(BuildContext context, String? supportUrl) {
    showDialog<void>(
      context: context,
      builder: (dialog) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('IT TUBE', style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.navy)),
        content: const Text(
          'Premium video streaming by Nexdi Code Lab.\n\nNeed help? Reach out to support from the website.',
          style: TextStyle(fontSize: 13, height: 1.5),
        ),
        actions: [
          if (supportUrl != null)
            TextButton(
              onPressed: () => launchUrl(Uri.parse(supportUrl), mode: LaunchMode.externalApplication),
              child: const Text('Support'),
            ),
          TextButton(onPressed: () => Navigator.pop(dialog), child: const Text('Close')),
        ],
      ),
    );
  }
}
