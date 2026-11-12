import 'package:ai_commerce_mobile/core/config/env_config.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  tearDown(() {
    EnvConfig.environment = AppEnvironment.development;
  });

  group('Release.3 — R3.2 Production API Base URL Safety', () {
    test('Case A: Development without API_BASE_URL keeps development fallback', () {
      expect(
        EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'local',
          apiBaseUrlOverride: '',
          isWebOverride: false,
          platformOverride: TargetPlatform.android,
        ),
        EnvConfig.defaultPhysicalDeviceUrl,
      );

      expect(
        EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'local',
          apiBaseUrlOverride: '',
          isWebOverride: false,
          platformOverride: TargetPlatform.windows,
        ),
        EnvConfig.defaultLocalhostUrl,
      );

      EnvConfig.environment = AppEnvironment.development;
      expect(EnvConfig.apiBaseUrl, 'http://127.0.0.1:8000/api/v1');
    });

    test('Case B: Production with HTTPS API_BASE_URL returns configured URL', () {
      const prodUrl = 'https://api.example.test/api/v1';

      expect(
        EnvConfig.resolveApiBaseUrl(
          isReleaseMode: true,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: '',
          apiBaseUrlOverride: prodUrl,
        ),
        prodUrl,
      );

      expect(
        EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.production,
          appEnvOverride: '',
          apiBaseUrlOverride: prodUrl,
        ),
        prodUrl,
      );

      expect(
        EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'production',
          apiBaseUrlOverride: prodUrl,
        ),
        prodUrl,
      );
    });

    test('Case C: Production without API_BASE_URL throws configuration failure and never falls back to localhost', () {
      expect(
        () => EnvConfig.resolveApiBaseUrl(
          isReleaseMode: true,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: '',
          apiBaseUrlOverride: '',
        ),
        throwsStateError,
      );

      expect(
        () => EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'production',
          apiBaseUrlOverride: '   ',
        ),
        throwsStateError,
      );

      EnvConfig.environment = AppEnvironment.production;
      expect(() => EnvConfig.apiBaseUrl, throwsStateError);
    });

    test('Case D: Production rejects HTTP API_BASE_URL', () {
      expect(
        () => EnvConfig.resolveApiBaseUrl(
          isReleaseMode: true,
          apiBaseUrlOverride: 'http://api.example.test/api/v1',
        ),
        throwsStateError,
      );

      expect(
        () => EnvConfig.resolveApiBaseUrl(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.production,
          apiBaseUrlOverride: 'http://api.example.test/api/v1',
        ),
        throwsStateError,
      );
    });

    test('Case E: Production rejects localhost, emulator, and LAN hosts even over HTTPS', () {
      const blockedUrls = [
        'https://127.0.0.1:8000/api/v1',
        'https://localhost:8000/api/v1',
        'https://10.0.2.2:8000/api/v1',
        'https://192.168.0.101:8000/api/v1',
      ];

      for (final url in blockedUrls) {
        expect(
          () => EnvConfig.resolveApiBaseUrl(
            isReleaseMode: true,
            apiBaseUrlOverride: url,
          ),
          throwsStateError,
          reason: 'Expected $url to be rejected in production',
        );
      }
    });
  });
}

