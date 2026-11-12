import 'package:flutter/foundation.dart';

enum AppEnvironment { development, staging, production }

class EnvConfig {
  static const String defaultAndroidEmulatorUrl =
      'http://10.0.2.2:8000/api/v1';

  static const String defaultLocalhostUrl =
      'http://127.0.0.1:8000/api/v1';

  static const String defaultPhysicalDeviceUrl =
      'http://127.0.0.1:8000/api/v1';

  static AppEnvironment environment = AppEnvironment.development;

  static const String _appEnvFromDefine = String.fromEnvironment('APP_ENV');
  static const String _aiDriverFromDefine = String.fromEnvironment('AI_DRIVER');
  static const String _apiBaseUrlFromDefine =
      String.fromEnvironment('API_BASE_URL');

  static bool resolveIsProduction({
    bool isReleaseMode = kReleaseMode,
    AppEnvironment? environmentOverride,
    String? appEnvOverride,
  }) {
    final activeEnv = environmentOverride ?? environment;
    final activeAppEnv = (appEnvOverride ?? _appEnvFromDefine).trim().toLowerCase();
    return isReleaseMode ||
        activeEnv == AppEnvironment.production ||
        activeAppEnv == 'production';
  }

  static bool get isProduction => resolveIsProduction();

  static String resolveDefaultAiDriver({
    bool isReleaseMode = kReleaseMode,
    AppEnvironment? environmentOverride,
    String? appEnvOverride,
    String? aiDriverOverride,
  }) {
    final prod = resolveIsProduction(
      isReleaseMode: isReleaseMode,
      environmentOverride: environmentOverride,
      appEnvOverride: appEnvOverride,
    );
    final configuredDriver =
        (aiDriverOverride ?? _aiDriverFromDefine).trim().toLowerCase();

    if (prod) {
      if (configuredDriver.isNotEmpty && configuredDriver != 'mock') {
        return configuredDriver;
      }
      return 'openai';
    }

    if (configuredDriver.isNotEmpty) {
      return configuredDriver;
    }

    return 'mock';
  }

  static String get defaultAiDriver => resolveDefaultAiDriver();

  static String resolveApiBaseUrl({
    bool isReleaseMode = kReleaseMode,
    AppEnvironment? environmentOverride,
    String? appEnvOverride,
    String? apiBaseUrlOverride,
    bool? isWebOverride,
    TargetPlatform? platformOverride,
  }) {
    final prod = resolveIsProduction(
      isReleaseMode: isReleaseMode,
      environmentOverride: environmentOverride,
      appEnvOverride: appEnvOverride,
    );
    final configuredUrl =
        (apiBaseUrlOverride ?? _apiBaseUrlFromDefine).trim();

    if (prod) {
      if (configuredUrl.isEmpty) {
        throw StateError(
          'Production builds require --dart-define=API_BASE_URL=https://...',
        );
      }

      final parsed = Uri.tryParse(configuredUrl);
      if (parsed == null ||
          parsed.scheme.toLowerCase() != 'https' ||
          parsed.host.isEmpty) {
        throw StateError(
          'Production API_BASE_URL must be a valid HTTPS URL with a host.',
        );
      }

      if (_isLocalOrPrivateDevelopmentHost(parsed.host.toLowerCase())) {
        throw StateError(
          'Production API_BASE_URL cannot target localhost or a local/private network host.',
        );
      }

      return configuredUrl;
    }

    if (configuredUrl.isNotEmpty) {
      return configuredUrl;
    }

    final web = isWebOverride ?? kIsWeb;
    final platform = platformOverride ?? defaultTargetPlatform;

    if (web ||
        platform == TargetPlatform.windows ||
        platform == TargetPlatform.linux ||
        platform == TargetPlatform.macOS) {
      return defaultLocalhostUrl;
    }

    return defaultPhysicalDeviceUrl;
  }

  static bool _isLocalOrPrivateDevelopmentHost(String host) {
    if (host == 'localhost' ||
        host == '127.0.0.1' ||
        host == '10.0.2.2' ||
        host == '::1' ||
        host == '0.0.0.0' ||
        host.endsWith('.localhost')) {
      return true;
    }
    if (host.startsWith('192.168.') || host.startsWith('10.')) {
      return true;
    }
    final octets = host.split('.');
    if (octets.length == 4 && octets[0] == '172') {
      final second = int.tryParse(octets[1]);
      if (second != null && second >= 16 && second <= 31) {
        return true;
      }
    }
    return false;
  }

  /// Active Base URL for the Laravel Backend REST API V1
  static String get apiBaseUrl => resolveApiBaseUrl();

  static const int connectTimeoutMs = 15000;
  static const int receiveTimeoutMs = 35000;
  static const int sendTimeoutMs = 15000;
}