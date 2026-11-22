import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../services/api_service.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _email = TextEditingController();
  final _token = TextEditingController();
  final _password = TextEditingController();
  bool _sent = false;
  bool _loading = false;
  String? _error;
  String? _message;

  Future<void> _send() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<ApiService>().forgotPassword(_email.text.trim());
      setState(() {
        _sent = true;
        _message = 'If the email exists, a reset code has been sent. Enter the code from your email below.';
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Unable to connect. Please try again.');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _reset() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<ApiService>().resetPassword(email: _email.text.trim(), token: _token.text.trim(), password: _password.text);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password reset. Please sign in.')));
      Navigator.of(context).pop();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Unable to reset password. Please try again.');
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _email.dispose();
    _token.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reset password')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Icon(Icons.lock_reset_rounded, size: 58, color: AppColors.primary),
            const SizedBox(height: 16),
            const Text('Forgot your password?', textAlign: TextAlign.center, style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.navy)),
            const SizedBox(height: 8),
            const Text('Enter your account email and we will send a reset code.', textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: AppColors.muted)),
            const SizedBox(height: 24),
            if (_error != null) ...[
              Container(
                padding: const EdgeInsets.all(13),
                decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(12)),
                child: Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5)),
              ),
              const SizedBox(height: 14),
            ],
            if (_message != null) ...[
              Container(
                padding: const EdgeInsets.all(13),
                decoration: BoxDecoration(color: const Color(0xFFE9F9F2), borderRadius: BorderRadius.circular(12)),
                child: Text(_message!, style: const TextStyle(color: Color(0xFF047857), fontSize: 12.5)),
              ),
              const SizedBox(height: 14),
            ],
            TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email address', prefixIcon: Icon(Icons.mail_outline_rounded, size: 20))),
            const SizedBox(height: 14),
            if (!_sent)
              FilledButton(
                onPressed: _loading ? null : _send,
                child: _loading ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white)) : const Text('Send reset code'),
              )
            else ...[
              TextField(controller: _token, decoration: const InputDecoration(labelText: 'Reset code from email', prefixIcon: Icon(Icons.pin_outlined, size: 20))),
              const SizedBox(height: 14),
              TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'New password', prefixIcon: Icon(Icons.lock_outline_rounded, size: 20))),
              const SizedBox(height: 18),
              FilledButton(
                onPressed: _loading ? null : _reset,
                child: _loading ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white)) : const Text('Reset password'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
