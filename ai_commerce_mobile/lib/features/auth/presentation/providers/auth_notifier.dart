import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/errors/failure.dart';
import '../../../../core/providers/core_providers.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  final storage = ref.watch(secureStorageProvider);
  return AuthRepositoryImpl(apiClient: apiClient, storage: storage);
});

sealed class AuthState {
  const AuthState();
}

class AuthInitial extends AuthState {
  const AuthInitial();
}

class AuthLoading extends AuthState {
  const AuthLoading();
}

class Authenticated extends AuthState {
  final UserModel user;
  const Authenticated(this.user);
}

class Unauthenticated extends AuthState {
  final String? message;
  const Unauthenticated([this.message]);
}

class AuthError extends AuthState {
  final String message;
  final Failure failure;
  const AuthError({required this.message, required this.failure});
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository repository;

  AuthNotifier({required this.repository})
      : super(const AuthInitial()) {
    checkAuthStatus();
  }

  Future<void> checkAuthStatus() async {
    final hasToken = await repository.isAuthenticated();
    if (!hasToken) {
      state = const Unauthenticated();
      return;
    }

    try {
      final user = await repository.getCurrentUser();
      state = Authenticated(user);
    } catch (_) {
      await repository.logout();
      state = const Unauthenticated('انتهت صلاحية الجلسة.');
    }
  }

  Future<bool> login(String email, String password) async {
    state = const AuthLoading();
    try {
      final res = await repository.login(email: email, password: password);
      state = Authenticated(res.user);
      return true;
    } on Failure catch (f) {
      state = AuthError(message: f.message, failure: f);
      return false;
    } catch (e) {
      final f = ServerFailure(message: e.toString());
      state = AuthError(message: f.message, failure: f);
      return false;
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    state = const AuthLoading();
    try {
      final res = await repository.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      state = Authenticated(res.user);
      return true;
    } on Failure catch (f) {
      state = AuthError(message: f.message, failure: f);
      return false;
    } catch (e) {
      final f = ServerFailure(message: e.toString());
      state = AuthError(message: f.message, failure: f);
      return false;
    }
  }

  Future<void> logout() async {
    state = const AuthLoading();
    await repository.logout();
    state = const Unauthenticated();
  }
}

final authNotifierProvider =
    StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final repository = ref.watch(authRepositoryProvider);
  return AuthNotifier(repository: repository);
});
