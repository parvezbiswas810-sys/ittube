import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/route_observer.dart';
import 'core/theme.dart';
import 'providers/auth_provider.dart';
import 'providers/config_provider.dart';
import 'providers/feed_provider.dart';
import 'providers/notification_provider.dart';
import 'screens/splash_screen.dart';
import 'services/api_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.dark,
  ));

  final client = ApiClient();
  final api = ApiService(client);

  runApp(
    MultiProvider(
      providers: [
        Provider<ApiService>.value(value: api),
        ChangeNotifierProvider(create: (_) => AuthProvider(api)..init()),
        ChangeNotifierProvider(create: (_) => ConfigProvider(api)..load()),
        ChangeNotifierProvider(create: (_) => FeedProvider(api)),
        ChangeNotifierProvider(create: (_) => NotificationProvider(api)),
      ],
      child: const ItTubeApp(),
    ),
  );
}

class ItTubeApp extends StatelessWidget {
  const ItTubeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'IT TUBE',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      navigatorObservers: [appRouteObserver],
      home: const SplashScreen(),
    );
  }
}
