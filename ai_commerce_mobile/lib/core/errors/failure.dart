abstract class Failure {
  final String message;
  final int? statusCode;
  final dynamic errors;

  const Failure({
    required this.message,
    this.statusCode,
    this.errors,
  });

  @override
  String toString() => message;
}

class ServerFailure extends Failure {
  const ServerFailure({
    required super.message,
    super.statusCode,
    super.errors,
  });
}

class NetworkFailure extends Failure {
  const NetworkFailure({
    super.message = 'تعذر الاتصال بالخادم. تحقق من اتصال الإنترنت أو إعدادات عنوان الخادم.',
    super.statusCode = 0,
  });
}

class AuthFailure extends Failure {
  const AuthFailure({
    super.message = 'انتهت صلاحية الجلسة أو بيانات الاعتماد غير صحيحة.',
    super.statusCode = 401,
    super.errors,
  });
}

class ValidationFailure extends Failure {
  final Map<String, List<String>> fieldErrors;

  const ValidationFailure({
    super.message = 'يرجى مراجعة البيانات المدخلة وتصحيح الأخطاء.',
    super.statusCode = 422,
    this.fieldErrors = const {},
  }) : super(errors: fieldErrors);

  String? getFirstErrorFor(String field) {
    if (fieldErrors.containsKey(field) && fieldErrors[field]!.isNotEmpty) {
      return fieldErrors[field]!.first;
    }
    return null;
  }
}

class RateLimitFailure extends Failure {
  final int? retryAfterSeconds;

  const RateLimitFailure({
    super.message = 'تم تجاوز معدل الطلبات المسموح به. يرجى الانتظار قليلاً.',
    super.statusCode = 429,
    this.retryAfterSeconds,
  });
}

class NotFoundFailure extends Failure {
  const NotFoundFailure({
    super.message = 'العنصر المطلوب غير موجود.',
    super.statusCode = 404,
  });
}

