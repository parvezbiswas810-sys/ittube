import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/widgets.dart';
import '../auth/login_screen.dart';
import '../channel/create_channel_screen.dart';
import '../downloads/downloads_screen.dart';
import '../channel/channel_screen.dart';
import '../notifications/notifications_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Future<void> _editProfile() async {
    final auth = context.read<AuthProvider>();
    final user = auth.user;
    if (user == null) return;
    final name = TextEditingController(text: user.displayName);
    final bio = TextEditingController(text: user.bio ?? '');
    File? avatar;

    final saved = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setDialogState) => AlertDialog(
          title: const Text('Edit profile'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                GestureDetector(
                  onTap: () async {
                    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85, maxWidth: 1200);
                    if (picked != null) setDialogState(() => avatar = File(picked.path));
                  },
                  child: CircleAvatar(
                    radius: 38,
                    backgroundColor: AppColors.light,
                    backgroundImage: avatar != null ? FileImage(avatar!) : null,
                    child: avatar == null ? const Icon(Icons.add_a_photo_outlined, color: AppColors.primary) : null,
                  ),
                ),
                const SizedBox(height: 14),
                TextField(controller: name, decoration: const InputDecoration(labelText: 'Display name')),
                const SizedBox(height: 12),
                TextField(controller: bio, maxLines: 3, decoration: const InputDecoration(labelText: 'Bio')),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('Cancel')),
            FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: const Text('Save')),
          ],
        ),
      ),
    );

    if (saved == true) {
      try {
        await auth.updateProfile(displayName: name.text.trim(), bio: bio.text.trim(), avatar: avatar);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated.')));
      } on ApiException catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      } catch (_) {}
    }
  }

  Future<void> _changePassword() async {
    final current = TextEditingController();
    final next = TextEditingController();
    final changed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Change password'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: current, obscureText: true, decoration: const InputDecoration(labelText: 'Current password')),
            const SizedBox(height: 12),
            TextField(controller: next, obscureText: true, decoration: const InputDecoration(labelText: 'New password')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: const Text('Update')),
        ],
      ),
    );

    if (changed == true) {
      try {
        await context.read<ApiService>().changePassword(current.text, next.text);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated.')));
      } on ApiException catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      } catch (_) {}
    }
  }

  Future<void> _deleteAccount() async {
    final password = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete account'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('This permanently deletes your account, channel and uploads. This cannot be undone.', style: TextStyle(fontSize: 12.5, height: 1.5)),
            const SizedBox(height: 12),
            TextField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm password')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('Cancel')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        await context.read<AuthProvider>().deleteAccount(password.text);
        if (!mounted) return;
        Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
      } on ApiException catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      } catch (_) {}
    }
  }

  void _showText(String title, String body) {
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(child: Text(body, style: const TextStyle(fontSize: 12.5, height: 1.6))),
        actions: [TextButton(onPressed: () => Navigator.of(dialogContext).pop(), child: const Text('Close'))],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    if (!auth.isAuthenticated || user == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Profile')),
        body: EmptyState(
          title: 'You are signed out',
          message: 'Sign in to manage your profile, channel and settings.',
          icon: Icons.account_circle_outlined,
          action: FilledButton(
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
            style: FilledButton.styleFrom(minimumSize: const Size(170, 46)),
            child: const Text('Sign in'),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.only(bottom: 30),
        children: [
          Container(
            margin: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppColors.navy, AppColors.primary]),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(3),
                  decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.white70, width: 2)),
                  child: ChannelAvatar(channel: user.channel, size: 58),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(user.displayName, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                      Text('@${user.username}', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      const SizedBox(height: 4),
                      Text(user.email ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white60, fontSize: 11)),
                    ],
                  ),
                ),
                IconButton(onPressed: _editProfile, icon: const Icon(Icons.edit_rounded, color: Colors.white, size: 20)),
              ],
            ),
          ),
          if (user.channel != null)
            _tile(Icons.account_box_outlined, 'My channel', 'Open @${user.channel!.handle}', () {
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChannelScreen(handle: user.channel!.handle)));
            }),
          if (user.channel == null)
            _tile(Icons.add_business_outlined, 'Create your channel', 'Publish videos under your own brand', () async {
              final created = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => const CreateChannelScreen()));
              if (created == true) auth.refresh();
            }),
          _tile(Icons.download_outlined, 'Downloads', 'Watch your saved videos offline', () {
            Navigator.of(context).push(MaterialPageRoute(builder: (_) => const DownloadsScreen()));
          }),
          _tile(Icons.notifications_none_rounded, 'Notifications', 'Uploads, subscribers and replies', () {
            Navigator.of(context).push(MaterialPageRoute(builder: (_) => const NotificationsScreen()));
          }),
          _tile(Icons.lock_outline_rounded, 'Change password', 'Keep your account secure', _changePassword),
          _tile(Icons.description_outlined, 'Terms of Service', 'Read the IT TUBE terms', () {
            _showText('Terms of Service', 'By using IT TUBE you agree to publish only content you own, keep the community safe and follow the moderation rules set by the administrator.');
          }),
          _tile(Icons.privacy_tip_outlined, 'Privacy Policy', 'How your data is used', () {
            _showText('Privacy Policy', 'IT TUBE stores your account details, channel information, uploads, comments and watch history to operate the service. You can clear your history at any time from Library.');
          }),
          _tile(Icons.info_outline_rounded, 'About IT TUBE', 'Nexdi Code Lab · Blue Orbit Edition', () {
            _showText('About IT TUBE', 'IT TUBE is a premium video streaming platform built by Nexdi Code Lab.\n\nVersion 1.0.0 · Blue Orbit Edition');
          }),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: OutlinedButton.icon(
              onPressed: () async {
                await auth.logout();
                if (!context.mounted) return;
                Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
              },
              icon: const Icon(Icons.logout_rounded, size: 18),
              label: const Text('Log out'),
            ),
          ),
          const SizedBox(height: 10),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: TextButton(
              onPressed: _deleteAccount,
              child: const Text('Delete my account', style: TextStyle(color: AppColors.danger, fontSize: 12.5)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tile(IconData icon, String title, String subtitle, VoidCallback onTap) {
    return ListTile(
      onTap: onTap,
      leading: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(color: AppColors.light, borderRadius: BorderRadius.circular(12)),
        child: Icon(icon, color: AppColors.primary, size: 20),
      ),
      title: Text(title, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.navy)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
      trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.muted, size: 20),
    );
  }
}
