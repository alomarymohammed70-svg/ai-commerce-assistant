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

  /// Active Base URL for the Laravel Backend REST API V1
  static String get apiBaseUrl {
    const fromEnv = String.fromEnvironment('API_BASE_URL');

    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }

    if (kIsWeb ||
        defaultTargetPlatform == TargetPlatform.windows ||
        defaultTargetPlatform == TargetPlatform.linux ||
        defaultTargetPlatform == TargetPlatform.macOS) {
      return defaultLocalhostUrl;
    }

    return defaultPhysicalDeviceUrl;
  }

  static const int connectTimeoutMs = 15000;
  static const int receiveTimeoutMs = 35000;
  static const int sendTimeoutMs = 15000;
}