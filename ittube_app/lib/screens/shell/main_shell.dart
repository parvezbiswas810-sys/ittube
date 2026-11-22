import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/auth_gate.dart';
import '../channel/create_channel_screen.dart';
import '../home/home_screen.dart';
import '../profile/you_tab.dart';
import '../shorts/shorts_screen.dart';
import '../subscriptions/subscriptions_screen.dart';
import '../video/upload_screen.dart';

class MainShell extends StatefulWidget {
  final int initialIndex;
  const MainShell({super.key, this.initialIndex = 0});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  late int _index = widget.initialIndex.clamp(0, 3);

  // Shorts is rebuilt on entry so its player is disposed (and audio stops)
  // whenever another tab is selected.
  List<Widget> get _screens => [
        const HomeScreen(),
        _index == 1 ? const ShortsScreen() : const SizedBox.shrink(),
        const SubscriptionsScreen(),
        const YouTab(),
      ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FeedProvider>().load();
      context.read<AuthProvider>().refresh();
    });
  }

  Future<void> _openUpload({String type = 'video'}) async {
    if (!await ensureSignedIn(context, action: 'upload videos')) return;
    if (!mounted) return;

    if (context.read<AuthProvider>().user?.channel == null) {
      await _promptCreateChannel();
      return;
    }

    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => UploadScreen(initialType: type)));
    if (mounted) context.read<FeedProvider>().load(refresh: true);
  }

  Future<void> _promptCreateChannel() async {
    final create = await showDialog<bool>(
      context: context,
      builder: (dialog) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Create your channel first', style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.navy)),
        content: const Text(
          'Every upload needs a channel. Set up your IT TUBE channel in a few steps — name, handle and photo.',
          style: TextStyle(fontSize: 13, height: 1.5),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialog, false), child: const Text('Later')),
          FilledButton(onPressed: () => Navigator.pop(dialog, true), child: const Text('Create channel')),
        ],
      ),
    );

    if (create == true && mounted) {
      await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const CreateChannelScreen()));
    }
  }

  Future<void> _openCreateSheet() async {
    if (!await ensureSignedIn(context, action: 'create content')) return;
    if (!mounted) return;

    final liveEnabled = context.read<ConfigProvider>().config.liveStreamEnabled;

    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(26))),
      builder: (sheet) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 10, 18, 18),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(width: 42, height: 4, decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2))),
              const SizedBox(height: 16),
              const Align(
                alignment: Alignment.centerLeft,
                child: Text('Create', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.navy)),
              ),
              const SizedBox(height: 14),
              _action(Icons.upload_rounded, 'Upload video', 'Share a long-form video with your audience', () {
                Navigator.pop(sheet);
                _openUpload();
              }),
              _action(Icons.bolt_rounded, 'Create short', 'Vertical video under 60 seconds', () {
                Navigator.pop(sheet);
                _openUpload(type: 'short');
              }),
              if (liveEnabled)
                _action(Icons.sensors_rounded, 'Go live', 'Start a live stream from your channel', () {
                  Navigator.pop(sheet);
                  setState(() => _index = 1);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Live streaming is managed from your creator dashboard.')),
                  );
                }),
            ],
          ),
        ),
      ),
    );
  }

  Widget _action(IconData icon, String title, String subtitle, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: AppColors.lavenderSoft, borderRadius: BorderRadius.circular(18), border: Border.all(color: AppColors.border)),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(gradient: AppGradients.violetBrand, borderRadius: BorderRadius.circular(14)),
                child: Icon(icon, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: AppColors.navy)),
                    const SizedBox(height: 2),
                    Text(subtitle, style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: AppColors.muted),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _index, children: _screens),
      bottomNavigationBar: Container(
        margin: const EdgeInsets.fromLTRB(12, 0, 12, 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: AppColors.border),
          boxShadow: AppShadows.card,
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: NavigationBar(
            selectedIndex: _index >= 2 ? _index + 1 : _index,
            onDestinationSelected: (i) {
              if (i == 2) {
                _openCreateSheet();
                return;
              }
              setState(() => _index = i > 2 ? i - 1 : i);
            },
            destinations: const [
              NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded), label: 'Home'),
              NavigationDestination(icon: Icon(Icons.bolt_outlined), selectedIcon: Icon(Icons.bolt_rounded), label: 'Shorts'),
              NavigationDestination(icon: Icon(Icons.add_circle_outline_rounded), selectedIcon: Icon(Icons.add_circle_rounded), label: 'Create'),
              NavigationDestination(icon: Icon(Icons.subscriptions_outlined), selectedIcon: Icon(Icons.subscriptions_rounded), label: 'Subs'),
              NavigationDestination(icon: Icon(Icons.person_outline_rounded), selectedIcon: Icon(Icons.person_rounded), label: 'You'),
            ],
          ),
        ),
      ),
    );
  }
}
