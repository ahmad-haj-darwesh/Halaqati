import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../../../core/errors/api_exception.dart';
import '../../data/auth_repository.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class LoginState extends Equatable {
  const LoginState();

  @override
  List<Object?> get props => [];
}

class LoginInitial extends LoginState {}

class LoginLoading extends LoginState {}

class LoginSuccess extends LoginState {
  final String role;

  const LoginSuccess({required this.role});

  @override
  List<Object?> get props => [role];
}

class LoginFailure extends LoginState {
  final String message;

  const LoginFailure({required this.message});

  @override
  List<Object?> get props => [message];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class LoginCubit extends Cubit<LoginState> {
  final AuthRepository _authRepo;

  LoginCubit(this._authRepo) : super(LoginInitial());

  Future<void> login({required String email, required String password}) async {
    emit(LoginLoading());
    try {
      final result = await _authRepo.login(
        email: email,
        password: password,
      );
      emit(LoginSuccess(role: result.role));
    } catch (e) {
      final errorMessage = friendlyErrorMessage(e);
      emit(LoginFailure(message: errorMessage));
    }
  }
}