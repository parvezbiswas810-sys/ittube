import 'dart:async';

import 'package:app_links/app_links.dart';
import 'package:url_launcher/url_launcher.dart';

/// Completes Google sign-in in the system browser.
///
/// The native Play-services flow needs an Android OAuth client (package +
/// SHA-1) registered in the same Cloud project as the Web client. When that
/// registration is missing or the device has no Play services, this fallback
/// opens the backend's OAuth entry point in the browser; the backend performs
/// the code exchange with the Web client and returns a Sanctum token through
/// the `com.nexdicodelab.ittube://oauth` deep link.
class GoogleBrowserAuth {
  static const String scheme = 'com.nexdicodelab.ittube';
  static const String host = 'oauth';
  static const Duration _timeout = Duration(minutes: 4);

  final AppLinks _links = AppLinks();

  Uri startUri(String apiBaseUrl) {
    final base = Uri.parse(apiBaseUrl);
    return Uri(
      scheme: base.scheme,
      host: base.host,
      port: base.hasPort ? base.port : null,
      path: '/auth/google/redirect',
    );
  }

  /// Returns the API token, or null when the user closed the browser without
  /// finishing (or the deep link never arrived).
  Future<String?> signIn({required String apiBaseUrl}) async {
    final completer = Completer<String?>();

    void handle(Uri uri) {
      if (uri.scheme != scheme || uri.host != host) return;
      if (!completer.isCompleted) {
        completer.complete(uri.queryParameters['token']);
      }
    }

    final subscription = _links.uriLinkStream.listen(handle, onError: (_) {});

    try {
      final initial = await _links.getInitialLink();
      if (initial != null) handle(initial);

      final launched = await launchUrl(startUri(apiBaseUrl), mode: LaunchMode.externalApplication);
      if (!launched) {
        throw Exception('No browser is available to open the Google sign-in page.');
      }

      return await completer.future.timeout(_timeout, onTimeout: () => null);
    } finally {
      await subscription.cancel();
    }
  }
}
