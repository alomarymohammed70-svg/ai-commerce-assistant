import 'user_model.dart';

class AuthResponseModel {
  final UserModel user;
  final String token;
  final String tokenType;

  const AuthResponseModel({
    required this.user,
    required this.token,
    this.tokenType = 'Bearer',
  });

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    final payload = json['data'] is Map<String, dynamic>
        ? json['data'] as Map<String, dynamic>
        : json;
    return AuthResponseModel(
      user: UserModel.fromJson(payload['user'] as Map<String, dynamic>),
      token: payload['token'] as String? ?? '',
      tokenType: payload['token_type'] as String? ?? 'Bearer',
    );
  }
}

