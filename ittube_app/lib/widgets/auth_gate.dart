import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../providers/auth_provider.dart';
import '../screens/auth/login_screen.dart';

/// Returns true when the user is signed in. Guests get a branded sheet that
/// explains why an account is required and offers sign in / registration.
Future<bool> ensureSignedIn(BuildContext context, {String action = 'continue'}) async {
  final auth = context.read<AuthProvider>();
  if (auth.isAuthenticated) return true;

  final signedIn = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.white,
    isScrollControlled: true,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (sheetContext) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 20),
            const Icon(Icons.lock_outline_rounded, size: 34, color: AppColors.primary),
            const SizedBox(height: 12),
            Text(
              'Sign in to $action',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.navy),
            ),
            const SizedBox(height: 6),
            const Text(
              'Create a free IT TUBE account to like videos, subscribe to channels, comment and upload.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12.5, color: AppColors.muted, height: 1.5),
            ),
            const SizedBox(height: 20),
            FilledButton(
              onPressed: () async {
                await Navigator.of(sheetContext).push(MaterialPageRoute(builder: (_) => const LoginScreen()));
                if (sheetContext.mounted) Navigator.of(sheetContext).pop(auth.isAuthenticated);
              },
              child: const Text('Sign in'),
            ),
            const SizedBox(height: 8),
            TextButton(
              onPressed: () => Navigator.of(sheetContext).pop(false),
              child: const Text('Not now'),
            ),
          ],
        ),
      ),
    ),
  );

  return signedIn ?? false;
}
