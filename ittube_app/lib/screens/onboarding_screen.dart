import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/theme.dart';
import '../widgets/widgets.dart';
import 'auth/login_screen.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _controller = PageController();
  int _index = 0;

  static const _pages = [
    _OnboardData(
      icon: Icons.play_circle_fill_rounded,
      title: 'Watch your favorite videos',
      body: 'Stream premium videos in stunning quality with a clean, distraction-free player built for IT TUBE.',
      accent: AppColors.primary,
    ),
    _OnboardData(
      icon: Icons.explore_rounded,
      title: 'Discover new creators',
      body: 'Explore trending channels across gaming, music, technology, entertainment and more.',
      accent: AppColors.cyan,
    ),
    _OnboardData(
      icon: Icons.bolt_rounded,
      title: 'Explore Shorts',
      body: 'Swipe through short, vertical videos and discover something new in seconds.',
      accent: AppColors.aqua,
    ),
    _OnboardData(
      icon: Icons.video_call_rounded,
      title: 'Create your own channel',
      body: 'Upload videos, build your audience and grow your channel — all from your phone.',
      accent: AppColors.navy,
    ),
  ];

  Future<void> _finish() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('ittube_onboarding_seen', true);
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 12, 0),
              child: Row(
                children: [
                  const AppLogo(size: 34),
                  const SizedBox(width: 10),
                  const Text('IT TUBE', style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.navy, letterSpacing: 1.4)),
                  const Spacer(),
                  TextButton(onPressed: _finish, child: const Text('Skip')),
                ],
              ),
            ),
            Expanded(
              child: PageView.builder(
                controller: _controller,
                itemCount: _pages.length,
                onPageChanged: (i) => setState(() => _index = i),
                itemBuilder: (context, i) {
                  final page = _pages[i];
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 210,
                          height: 210,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [page.accent.withValues(alpha: 0.16), AppColors.light],
                            ),
                          ),
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              Container(
                                width: 160,
                                height: 160,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(color: page.accent.withValues(alpha: 0.25), width: 1.5),
                                ),
                              ),
                              Icon(page.icon, size: 84, color: page.accent),
                            ],
                          ),
                        ),
                        const SizedBox(height: 40),
                        Text(page.title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 25, fontWeight: FontWeight.w800, color: AppColors.navy, height: 1.2, letterSpacing: -0.6)),
                        const SizedBox(height: 12),
                        Text(page.body, textAlign: TextAlign.center, style: const TextStyle(fontSize: 13.5, color: AppColors.muted, height: 1.6)),
                      ],
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(28, 8, 28, 26),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(_pages.length, (i) {
                      final active = i == _index;
                      return AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        margin: const EdgeInsets.symmetric(horizontal: 3),
                        width: active ? 22 : 7,
                        height: 7,
                        decoration: BoxDecoration(
                          color: active ? AppColors.primary : AppColors.border,
                          borderRadius: BorderRadius.circular(99),
                        ),
                      );
                    }),
                  ),
                  const SizedBox(height: 22),
                  FilledButton(
                    onPressed: () {
                      if (_index == _pages.length - 1) {
                        _finish();
                      } else {
                        _controller.nextPage(duration: const Duration(milliseconds: 320), curve: Curves.easeOut);
                      }
                    },
                    child: Text(_index == _pages.length - 1 ? 'Get Started' : 'Next'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OnboardData {
  final IconData icon;
  final String title;
  final String body;
  final Color accent;
  const _OnboardData({required this.icon, required this.title, required this.body, required this.accent});
}
