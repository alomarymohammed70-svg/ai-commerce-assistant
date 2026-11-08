import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/storage/secure_storage_service.dart';
import '../models/auth_response_model.dart';
import '../models/user_model.dart';

abstract class AuthRepository {
  Future<AuthResponseModel> login({
    required String email,
    required String password,
  });

  Future<AuthResponseModel> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  });

  Future<UserModel> getCurrentUser();

  Future<void> logout();

  Future<bool> isAuthenticated();
}

class AuthRepositoryImpl implements AuthRepository {
  final ApiClient apiClient;
  final SecureStorageService storage;

  AuthRepositoryImpl({
    required this.apiClient,
    required this.storage,
  });

  @override
  Future<AuthResponseModel> login({
    required String email,
    required String password,
  }) async {
    final response = await apiClient.post(
      ApiEndpoints.login,
      data: {
        'email': email,
        'password': password,
      },
    );

    final authResponse = AuthResponseModel.fromJson(
      response as Map<String, dynamic>,
    );
    await storage.saveToken(authResponse.token);
    await storage.saveUserData(
      id: authResponse.user.id.toString(),
      name: authResponse.user.name,
      email: authResponse.user.email,
    );

    return authResponse;
  }

  @override
  Future<AuthResponseModel> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await apiClient.post(
      ApiEndpoints.register,
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );

    final authResponse = AuthResponseModel.fromJson(
      response as Map<String, dynamic>,
    );
    await storage.saveToken(authResponse.token);
    await storage.saveUserData(
      id: authResponse.user.id.toString(),
      name: authResponse.user.name,
      email: authResponse.user.email,
    );

    return authResponse;
  }

  @override
  Future<UserModel> getCurrentUser() async {
    final response = await apiClient.get(ApiEndpoints.me);
    final data = response['data'] as Map<String, dynamic>;
    return UserModel.fromJson(data);
  }

  @override
  Future<void> logout() async {
    try {
      await apiClient.post(ApiEndpoints.logout);
    } catch (_) {
      // Clean storage regardless of server response
    } finally {
      await storage.clearAll();
    }
  }

  @override
  Future<bool> isAuthenticated() async {
    return await storage.hasToken();
  }
}
