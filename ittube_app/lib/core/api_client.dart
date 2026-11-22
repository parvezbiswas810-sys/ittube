import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  final String message;
  final Map<String, dynamic> errors;
  final int statusCode;

  ApiException(this.message, {this.errors = const {}, this.statusCode = 0});

  @override
  String toString() => message;
}

class UploadCancelledException implements Exception {
  const UploadCancelledException();

  @override
  String toString() => 'Upload cancelled.';
}

class ApiClient {
  static const String defaultBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.adsxuit.com/api/v1',
  );

  String baseUrl;
  String? token;
  final http.Client _client = http.Client();

  ApiClient({this.baseUrl = defaultBaseUrl});

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        if (token != null && token!.isNotEmpty) 'Authorization': 'Bearer $token',
      };

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    final cleanPath = path.startsWith('/') ? path.substring(1) : path;
    final uri = Uri.parse('$baseUrl/$cleanPath');
    if (query == null || query.isEmpty) return uri;
    return uri.replace(queryParameters: {
      ...uri.queryParameters,
      for (final entry in query.entries)
        if (entry.value != null) entry.key: entry.value.toString(),
    });
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    final response = await _client.get(_uri(path, query), headers: _headers).timeout(const Duration(seconds: 30));
    return _decode(response);
  }

  Future<Map<String, dynamic>> post(String path, {Map<String, dynamic>? body}) async {
    final response = await _client
        .post(_uri(path), headers: {..._headers, 'Content-Type': 'application/json'}, body: jsonEncode(body ?? {}))
        .timeout(const Duration(seconds: 45));
    return _decode(response);
  }

  Future<Map<String, dynamic>> put(String path, {Map<String, dynamic>? body}) async {
    final response = await _client
        .put(_uri(path), headers: {..._headers, 'Content-Type': 'application/json'}, body: jsonEncode(body ?? {}))
        .timeout(const Duration(seconds: 45));
    return _decode(response);
  }

  /// Streams a binary response (used for video downloads) to [sink].
  Future<void> downloadTo(String path, IOSink sink, {void Function(int received)? onProgress}) async {
    final request = http.Request('GET', _uri(path))..headers.addAll(_headers);
    final response = await _client.send(request).timeout(const Duration(seconds: 60));
    if (response.statusCode != 200) {
      final body = await response.stream.bytesToString();
      _decode(http.Response(body, response.statusCode, headers: response.headers));
      return;
    }
    var received = 0;
    await for (final chunk in response.stream) {
      sink.add(chunk);
      received += chunk.length;
      onProgress?.call(received);
    }
    await sink.flush();
  }

  Future<Map<String, dynamic>> delete(String path) async {
    final response = await _client.delete(_uri(path), headers: _headers).timeout(const Duration(seconds: 30));
    return _decode(response);
  }

  Future<Map<String, dynamic>> upload(
    String path, {
    required Map<String, String> fields,
    File? file,
    String fieldName = 'video',
    File? thumbnail,
    Map<String, File> extraFiles = const {},
    void Function(double progress)? onProgress,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll(_headers);
    request.fields.addAll(fields);

    if (file != null) {
      final total = await file.length();
      var sent = 0;
      final stream = file.openRead().transform(StreamTransformer<List<int>, List<int>>.fromHandlers(
        handleData: (data, sink) {
          sent += data.length;
          if (total > 0) onProgress?.call((sent / total).clamp(0, 1));
          sink.add(data);
        },
      ));

      request.files.add(http.MultipartFile(fieldName, http.ByteStream(stream), total, filename: file.uri.pathSegments.last));
    }
    if (thumbnail != null) {
      request.files.add(await http.MultipartFile.fromPath('thumbnail', thumbnail.path));
    }
    for (final entry in extraFiles.entries) {
      request.files.add(await http.MultipartFile.fromPath(entry.key, entry.value.path));
    }

    final streamed = await request.send().timeout(const Duration(minutes: 15));
    final response = await http.Response.fromStream(streamed);
    return _decode(response);
  }

  Future<Map<String, dynamic>> uploadBytes(
    String path, {
    required Map<String, String> fields,
    required String fieldName,
    required List<int> bytes,
    required String filename,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll(_headers);
    request.fields.addAll(fields);
    request.files.add(http.MultipartFile.fromBytes(fieldName, bytes, filename: filename));

    final streamed = await request.send().timeout(const Duration(minutes: 5));
    return _decode(await http.Response.fromStream(streamed));
  }

  Future<Map<String, dynamic>> uploadWithThumbnail(
    String path, {
    required Map<String, String> fields,
    File? thumbnail,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll(_headers);
    request.fields.addAll(fields);
    if (thumbnail != null) {
      request.files.add(await http.MultipartFile.fromPath('thumbnail', thumbnail.path));
    }

    final streamed = await request.send().timeout(const Duration(minutes: 5));
    return _decode(await http.Response.fromStream(streamed));
  }

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> json;
    try {
      final decoded = jsonDecode(response.body);
      json = decoded is Map<String, dynamic> ? decoded : {'data': decoded};
    } catch (_) {
      throw ApiException('Unexpected server response. Please try again.', statusCode: response.statusCode);
    }

    if (response.statusCode >= 200 && response.statusCode < 300 && json['success'] == true) {
      return json;
    }

    throw ApiException(
      json['message']?.toString() ?? 'Request failed. Please try again.',
      errors: (json['errors'] as Map<String, dynamic>?) ?? const {},
      statusCode: response.statusCode,
    );
  }
}
