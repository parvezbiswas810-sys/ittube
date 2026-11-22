import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/theme.dart';
import '../providers/auth_provider.dart';
import '../providers/config_provider.dart';
import '../widgets/widgets.dart';
import 'auth/login_screen.dart';
import 'common/maintenance_screen.dart';
import 'onboarding_screen.dart';
import 'shell/main_shell.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200))..forward();
  late final Animation<double> _fade = CurvedAnimation(parent: _controller, curve: const Interval(0, 0.7, curve: Curves.easeOut));
  late final Animation<double> _scale = Tween<double>(begin: 0.82, end: 1).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOutBack));

  @override
  void initState() {
    super.initState();
    _boot();
  }

  Future<void> _boot() async {
    await Future.delayed(const Duration(milliseconds: 1500));
    if (!mounted) return;

    final config = context.read<ConfigProvider>();
    if (!config.loaded) {
      await config.load();
    }
    if (!mounted) return;

    if (config.config.maintenance) {
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => MaintenanceScreen(message: config.config.maintenanceMessage)));
      return;
    }

    final auth = context.read<AuthProvider>();
    if (auth.isAuthenticated) {
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MainShell()));
      return;
    }

    if (config.config.guestModeEnabled) {
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MainShell()));
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final seen = prefs.getBool('ittube_onboarding_seen') ?? false;
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => seen ? const LoginScreen() : const OnboardingScreen()));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFFFFFFF), Color(0xFFF4F9FF), Color(0xFFEAF5FF)],
          ),
        ),
        child: Stack(
          children: [
            Positioned(top: -90, right: -70, child: _orbit(280, AppColors.sky.withValues(alpha: 0.18))),
            Positioned(bottom: -120, left: -90, child: _orbit(320, AppColors.cyan.withValues(alpha: 0.14))),
            Center(
              child: FadeTransition(
                opacity: _fade,
                child: ScaleTransition(
                  scale: _scale,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(30),
                          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.28), blurRadius: 44, offset: const Offset(0, 18))],
                        ),
                        child: const AppLogo(size: 108),
                      ),
                      const SizedBox(height: 24),
                      const Text(
                        'IT TUBE',
                        style: TextStyle(fontSize: 34, fontWeight: FontWeight.w900, color: AppColors.navy, letterSpacing: 2),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'PREMIUM VIDEO STREAMING',
                        style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: AppColors.muted, letterSpacing: 3),
                      ),
                      const SizedBox(height: 40),
                      const SizedBox(
                        width: 26,
                        height: 26,
                        child: CircularProgressIndicator(strokeWidth: 2.6, color: AppColors.primary),
                      ),
                      const SizedBox(height: 18),
                      const Text('Loading your experience…', style: TextStyle(fontSize: 11.5, color: AppColors.muted)),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 30,
              child: Column(
                children: const [
                  Text('NEXDI CODE LAB', style: TextStyle(fontSize: 10, letterSpacing: 3.4, fontWeight: FontWeight.w700, color: AppColors.muted)),
                  SizedBox(height: 4),
                  Text('Blue Orbit Edition', style: TextStyle(fontSize: 10.5, color: AppColors.muted)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _orbit(double size, Color color) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: color, width: 1.5)),
      );
}
