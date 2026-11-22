import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/config_provider.dart';
import '../../services/api_service.dart';
import '../../services/google_auth.dart';
import '../../services/google_browser_auth.dart';
import '../../widgets/widgets.dart';
import '../shell/main_shell.dart';

/// Google-only entry point. Accounts are created on first sign-in, so there is
/// no separate registration screen.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  String? _error;
  bool _googleBusy = false;

  late final AnimationController _intro = AnimationController(vsync: this, duration: const Duration(milliseconds: 750))..forward();
  late final Animation<double> _fade = CurvedAnimation(parent: _intro, curve: Curves.easeOutCubic);
  late final Animation<Offset> _rise =
      Tween<Offset>(begin: const Offset(0, 0.05), end: Offset.zero).animate(CurvedAnimation(parent: _intro, curve: Curves.easeOutCubic));

  @override
  void dispose() {
    _intro.dispose();
    super.dispose();
  }

  Future<void> _googleSignIn() async {
    final auth = context.read<AuthProvider>();
    final config = context.read<ConfigProvider>().config;
    final api = context.read<ApiService>();
    final clientId = config.googleClientId;

    setState(() {
      _error = null;
      _googleBusy = true;
    });

    try {
      if (clientId == null || clientId.isEmpty) {
        setState(() => _error = 'Google sign-in is not configured yet. Add the Web client ID in the admin panel.');
        return;
      }
      final idToken = await obtainGoogleIdToken(serverClientId: clientId);
      if (idToken == null) {
        setState(() => _error = 'Google did not return a sign-in token. Please try again.');
        return;
      }
      await auth.signInWithGoogle(idToken);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MainShell()));
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (e) {
      // Play services refused the native flow: finish in the system browser.
      final nativeError = describeGoogleError(e);
      if (!mounted) return;
      await _googleBrowserSignIn(api: api, nativeError: nativeError);
    } finally {
      if (mounted) setState(() => _googleBusy = false);
    }
  }

  Future<void> _googleBrowserSignIn({ApiService? api, String? nativeError}) async {
    final service = api ?? context.read<ApiService>();
    final auth = context.read<AuthProvider>();

    setState(() {
      _error = null;
      _googleBusy = true;
    });

    try {
      final token = await GoogleBrowserAuth().signIn(apiBaseUrl: service.client.baseUrl);
      if (token == null || token.isEmpty) {
        if (mounted) setState(() => _error = nativeError ?? 'Google sign-in was not completed.');
        return;
      }
      await auth.loginWithToken(token);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MainShell()));
    } catch (e) {
      if (mounted) setState(() => _error = 'Browser sign-in failed: $e');
    } finally {
      if (mounted) setState(() => _googleBusy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = context.watch<ConfigProvider>().config;
    final loading = context.watch<AuthProvider>().loading;
    final busy = _googleBusy || loading;

    return Scaffold(
      body: DecoratedBox(
        decoration: const BoxDecoration(gradient: AppGradients.aurora),
        child: SafeArea(
          child: Stack(
            children: [
              const _OrbitDecor(),
              SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 28),
                child: FadeTransition(
                  opacity: _fade,
                  child: SlideTransition(
                    position: _rise,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const SizedBox(height: 24),
                        Center(
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(34),
                              boxShadow: AppShadows.glow,
                            ),
                            child: const AppLogo(size: 92),
                          ),
                        ),
                        const SizedBox(height: 22),
                        const Text(
                          'IT TUBE',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 27, fontWeight: FontWeight.w900, color: AppColors.navy, letterSpacing: 5),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Premium video streaming',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 12.5, color: AppColors.muted, letterSpacing: 1.2),
                        ),
                        const SizedBox(height: 40),
                        const Text(
                          'Sign in to continue',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.navy, letterSpacing: -0.4),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'One tap with Google. Your channel, playlists and history follow you.',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 13, color: AppColors.muted, height: 1.5),
                        ),
                        const SizedBox(height: 26),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF2F2),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFFFBD5D5)),
                            ),
                            child: Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5, height: 1.45)),
                          ),
                          const SizedBox(height: 16),
                        ],
                        _GoogleButton(busy: busy, onTap: _googleSignIn),
                        const SizedBox(height: 8),
                        TextButton(
                          onPressed: busy ? null : () => _googleBrowserSignIn(),
                          child: const Text('Trouble signing in? Continue in browser', style: TextStyle(fontSize: 12)),
                        ),
                        if (config.guestModeEnabled) ...[
                          const SizedBox(height: 8),
                          OutlinedButton(
                            onPressed: busy
                                ? null
                                : () => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MainShell())),
                            style: OutlinedButton.styleFrom(
                              minimumSize: const Size.fromHeight(52),
                              foregroundColor: AppColors.navy,
                              backgroundColor: Colors.white,
                              side: const BorderSide(color: AppColors.border),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            ),
                            child: const Text('Browse as guest'),
                          ),
                        ],
                        const SizedBox(height: 26),
                        const _FeatureRow(),
                        const SizedBox(height: 26),
                        const Text(
                          'By continuing you agree to the Terms of Service and Privacy Policy.',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 11, color: AppColors.muted, height: 1.5),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _GoogleButton extends StatelessWidget {
  final bool busy;
  final VoidCallback onTap;
  const _GoogleButton({required this.busy, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: busy ? null : onTap,
        child: Container(
          height: 58,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: AppColors.border),
            boxShadow: AppShadows.card,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (busy)
                const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.4))
              else
                Container(
                  width: 30,
                  height: 30,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.border),
                  ),
                  child: const Text('G', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF4285F4))),
                ),
              const SizedBox(width: 12),
              const Text(
                'Continue with Google',
                style: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w700, color: AppColors.navy),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FeatureRow extends StatelessWidget {
  const _FeatureRow();

  @override
  Widget build(BuildContext context) {
    const items = [
      (Icons.play_circle_fill_rounded, 'Watch'),
      (Icons.bolt_rounded, 'Shorts'),
      (Icons.video_camera_front_rounded, 'Create'),
    ];

    return Row(
      children: [
        for (final (icon, label) in items)
          Expanded(
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              padding: const EdgeInsets.symmetric(vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.75),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(gradient: AppGradients.brand, borderRadius: BorderRadius.circular(11)),
                    child: Icon(icon, size: 18, color: Colors.white),
                  ),
                  const SizedBox(height: 8),
                  Text(label, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.navy)),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

/// Soft orbit rings echoing the IT TUBE logo.
class _OrbitDecor extends StatelessWidget {
  const _OrbitDecor();

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Stack(
        children: [
          Positioned(
            top: -120,
            right: -90,
            child: Container(
              width: 280,
              height: 280,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.10), width: 28),
              ),
            ),
          ),
          Positioned(
            top: 60,
            left: -110,
            child: Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.cyan.withValues(alpha: 0.12), width: 18),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
