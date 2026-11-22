import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/auth_gate.dart';
import '../../widgets/widgets.dart';

class CommentsSheet extends StatefulWidget {
  final Video video;
  const CommentsSheet({super.key, required this.video});

  @override
  State<CommentsSheet> createState() => _CommentsSheetState();
}

class _CommentsSheetState extends State<CommentsSheet> {
  final _controller = TextEditingController();
  List<Comment> _comments = [];
  bool _loading = true;
  bool _sending = false;
  int? _replyTo;
  String? _replyName;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final (comments, _) = await context.read<ApiService>().comments(widget.video.id);
      if (!mounted) return;
      setState(() {
        _comments = comments;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    if (!await ensureSignedIn(context, action: 'comment')) return;
    if (!mounted) return;
    setState(() => _sending = true);
    try {
      await context.read<ApiService>().addComment(widget.video.id, text, parentId: _replyTo);
      _controller.clear();
      setState(() {
        _replyTo = null;
        _replyName = null;
      });
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Unable to post comment.')));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _delete(Comment comment) async {
    try {
      await context.read<ApiService>().deleteComment(comment.id);
      await _load();
    } catch (_) {}
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SizedBox(
        height: MediaQuery.of(context).size.height * 0.78,
        child: Column(
          children: [
            Container(
              margin: const EdgeInsets.symmetric(vertical: 10),
              width: 42,
              height: 4,
              decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(99)),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18),
              child: Row(
                children: [
                  Text('${formatCount(widget.video.commentsCount)} comments', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.navy)),
                  const Spacer(),
                  IconButton(onPressed: () => Navigator.of(context).pop(), icon: const Icon(Icons.close_rounded, size: 20)),
                ],
              ),
            ),
            const Divider(),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _comments.isEmpty
                      ? const EmptyState(title: 'No comments yet', message: 'Be the first to comment on this video.', icon: Icons.chat_bubble_outline_rounded)
                      : ListView.builder(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          itemCount: _comments.length,
                          itemBuilder: (context, i) => _tile(_comments[i]),
                        ),
            ),
            const Divider(),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 12, 14),
              child: Column(
                children: [
                  if (_replyTo != null)
                    Row(
                      children: [
                        Text('Replying to $_replyName', style: const TextStyle(fontSize: 11.5, color: AppColors.primary)),
                        IconButton(
                          onPressed: () => setState(() {
                            _replyTo = null;
                            _replyName = null;
                          }),
                          icon: const Icon(Icons.close_rounded, size: 16),
                        ),
                      ],
                    ),
                  Row(
                    children: [
                      ChannelAvatar(channel: auth.user?.channel, size: 34),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: _controller,
                          minLines: 1,
                          maxLines: 3,
                          textInputAction: TextInputAction.send,
                          onSubmitted: (_) => _send(),
                          decoration: const InputDecoration(hintText: 'Add a comment…', contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10)),
                        ),
                      ),
                      const SizedBox(width: 6),
                      IconButton(
                        onPressed: _sending ? null : _send,
                        icon: _sending
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                            : const Icon(Icons.send_rounded, color: AppColors.primary),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _tile(Comment comment) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ChannelAvatar(channel: comment.user?.channel, size: 34),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text('@${comment.user?.username ?? 'user'}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.navy)),
                    const SizedBox(width: 6),
                    Text(timeAgo(comment.createdAt), style: const TextStyle(fontSize: 10.5, color: AppColors.muted)),
                  ],
                ),
                const SizedBox(height: 3),
                Text(comment.body, style: const TextStyle(fontSize: 13, height: 1.45, color: AppColors.text)),
                Row(
                  children: [
                    TextButton(
                      onPressed: () => setState(() {
                        _replyTo = comment.id;
                        _replyName = comment.user?.username ?? 'user';
                      }),
                      style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(50, 30)),
                      child: const Text('Reply', style: TextStyle(fontSize: 11.5)),
                    ),
                    if (comment.isMine)
                      TextButton(
                        onPressed: () => _delete(comment),
                        style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(50, 30)),
                        child: const Text('Delete', style: TextStyle(fontSize: 11.5, color: AppColors.danger)),
                      ),
                  ],
                ),
                if (comment.replies.isNotEmpty)
                  ...comment.replies.map((reply) => Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ChannelAvatar(channel: reply.user?.channel, size: 26),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('@${reply.user?.username ?? 'user'}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.navy)),
                                  Text(reply.body, style: const TextStyle(fontSize: 12.5, height: 1.4, color: AppColors.text)),
                                ],
                              ),
                            ),
                          ],
                        ),
                      )),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
