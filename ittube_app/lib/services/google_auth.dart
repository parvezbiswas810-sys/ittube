import 'package:flutter/services.dart';
import 'package:google_sign_in/google_sign_in.dart';

/// Requests a Google ID token on the device.
///
/// [serverClientId] is the Web OAuth client ID configured in the admin panel;
/// it makes Google issue a token whose audience the backend accepts. The
/// Android OAuth client (package + SHA-1) must exist in the same Google Cloud
/// project, otherwise Google returns a PlatformException (code 10 / 12500).
Future<String?> obtainGoogleIdToken({String? serverClientId}) async {
  final googleSignIn = GoogleSignIn(
    scopes: const ['email', 'profile'],
    serverClientId: serverClientId,
  );

  final account = await googleSignIn.signIn();
  if (account == null) return null;

  final auth = await account.authentication;
  return auth.idToken;
}

/// SHA-1 of the certificate this build was signed with, injected at build time
/// with --dart-define=BUILD_SHA1=... so users can compare it with the Google
/// Cloud console without running keytool.
const buildSha1 = String.fromEnvironment('BUILD_SHA1');

String describeGoogleError(Object error) {
  final sha = buildSha1.isEmpty ? 'not embedded in this build' : buildSha1;

  if (error is PlatformException) {
    final detail = (error.message ?? '').trim();
    switch (error.code) {
      case 'sign_in_failed':
      case '10':
        return 'Google sign-in failed (code 10, developer error).\n'
            'Register this SHA-1 for package com.nexdicodelab.ittube in the same '
            'Google Cloud project as the Web client ID:\n$sha\n'
            'Also check that the admin panel holds the Web application client ID, '
            'not the Android one.${detail.isEmpty ? '' : '\n($detail)'}';
      case '12500':
        return 'Google sign-in failed (code 12500). Google Play services are missing '
            'or out of date on this device — use a real phone or a Play-enabled emulator.';
      case '12501':
        return 'Google sign-in was cancelled.';
      case '12502':
        return 'Another Google sign-in is already in progress. Wait a moment and retry.';
      case 'network_error':
        return 'Network error while contacting Google. Check the connection and retry.';
      case 'sign_in_canceled':
        return 'Google sign-in was cancelled.';
    }
    return 'Google sign-in failed [${error.code}].${detail.isEmpty ? '' : '\n$detail'}';
  }
  return 'Google sign-in is not available on this device.';
}
