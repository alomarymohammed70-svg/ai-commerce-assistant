import 'package:dio/dio.dart';
import '../config/env_config.dart';
import '../errors/failure.dart';
import '../storage/secure_storage_service.dart';
import 'auth_interceptor.dart';

class ApiClient {
  late final Dio dio;
  final SecureStorageService storage;
  final void Function()? onSessionExpired;

  ApiClient({
    required this.storage,
    this.onSessionExpired,
    Dio? customDio,
  }) {
    dio = customDio ??
        Dio(
          BaseOptions(
            baseUrl: EnvConfig.apiBaseUrl,
            connectTimeout: const Duration(milliseconds: EnvConfig.connectTimeoutMs),
            receiveTimeout: const Duration(milliseconds: EnvConfig.receiveTimeoutMs),
            sendTimeout: const Duration(milliseconds: EnvConfig.sendTimeoutMs),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        );

    dio.interceptors.add(
      AuthInterceptor(
        storage: storage,
        onUnauthorized: () async {
          await storage.clearAuthData();
          onSessionExpired?.call();
        },
      ),
    );
  }

  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await dio.get(
        path,
        queryParameters: queryParameters,
        options: options,
      );
      return response.data;
    } on DioException catch (e) {
      throw _handleDioError(e);
    } catch (e) {
      throw ServerFailure(message: e.toString());
    }
  }

  Future<dynamic> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await dio.post(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return response.data;
    } on DioException catch (e) {
      throw _handleDioError(e);
    } catch (e) {
      throw ServerFailure(message: e.toString());
    }
  }

  Failure _handleDioError(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.connectionError:
        return const NetworkFailure();

      case DioExceptionType.badResponse:
        final statusCode = error.response?.statusCode;
        final body = error.response?.data;

        String message = 'حدث خطأ غير متوقع في الاتصال بالخادم.';
        dynamic errors;

        if (body is Map<String, dynamic>) {
          message = body['message']?.toString() ?? message;
          errors = body['errors'];
        }

        if (statusCode == 401) {
          return AuthFailure(message: message, errors: errors);
        }

        if (statusCode == 404) {
          return NotFoundFailure(message: message);
        }

        if (statusCode == 422) {
          final Map<String, List<String>> parsedErrors = {};
          if (errors is Map) {
            errors.forEach((key, val) {
              if (val is List) {
                parsedErrors[key.toString()] =
                    val.map((e) => e.toString()).toList();
              } else if (val != null) {
                parsedErrors[key.toString()] = [val.toString()];
              }
            });
          }
          return ValidationFailure(message: message, fieldErrors: parsedErrors);
        }

        if (statusCode == 429) {
          int? retryAfter;
          final headerVal = error.response?.headers.value('retry-after');
          if (headerVal != null) {
            retryAfter = int.tryParse(headerVal);
          }
          return RateLimitFailure(
            message: message,
            retryAfterSeconds: retryAfter,
          );
        }

        return ServerFailure(
          message: message,
          statusCode: statusCode,
          errors: errors,
        );

      case DioExceptionType.cancel:
        return const ServerFailure(message: 'تم إلغاء الطلب.');

      default:
        return ServerFailure(
          message: error.message ?? 'حدث خطأ غير متوقع.',
        );
    }
  }
}

