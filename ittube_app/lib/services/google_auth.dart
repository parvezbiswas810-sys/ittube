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

String describeGoogleError(Object error) {
  if (error is PlatformException) {
    switch (error.code) {
      case 'sign_in_failed':
      case '10':
        return 'Google sign-in failed. Check that this build\'s SHA-1 fingerprint is registered in the Google Cloud console.';
      case 'network_error':
        return 'Network error while contacting Google. Try again.';
      case 'sign_in_canceled':
        return 'Google sign-in was cancelled.';
    }
    return error.message ?? 'Google sign-in failed (${error.code}).';
  }
  return 'Google sign-in is not available on this device.';
}
