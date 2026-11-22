import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme.dart';
import '../../providers/config_provider.dart';
import '../../widgets/widgets.dart';

class MaintenanceScreen extends StatelessWidget {
  final String message;
  const MaintenanceScreen({super.key, required this.message});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(30),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const AppLogo(size: 84),
              const SizedBox(height: 26),
              const Text('We will be right back', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.navy)),
              const SizedBox(height: 10),
              Text(message, textAlign: TextAlign.center, style: const TextStyle(fontSize: 13.5, color: AppColors.muted, height: 1.6)),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: () async {
                  await context.read<ConfigProvider>().load();
                  if (!context.mounted) return;
                  if (!context.read<ConfigProvider>().config.maintenance) {
                    Navigator.of(context).pushReplacementNamed('/');
                  }
                },
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text('Check again'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
