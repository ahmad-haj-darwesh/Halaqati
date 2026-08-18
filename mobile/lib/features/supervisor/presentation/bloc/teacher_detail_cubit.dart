import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../../../core/errors/api_exception.dart';
import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class TeacherDetailState extends Equatable {
  const TeacherDetailState();

  @override
  List<Object?> get props => [];
}

class TeacherDetailLoading extends TeacherDetailState {}

class TeacherDetailLoaded extends TeacherDetailState {
  final TeacherDetail detail;

  const TeacherDetailLoaded(this.detail);

  @override
  List<Object?> get props => [detail];
}

class TeacherDetailError extends TeacherDetailState {
  final String message;

  const TeacherDetailError(this.message);

  @override
  List<Object?> get props => [message];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class TeacherDetailCubit extends Cubit<TeacherDetailState> {
  final SupervisorRepository _repo;

  TeacherDetailCubit(this._repo) : super(TeacherDetailLoading());

  Future<void> loadDetail(int teacherId) async {
    emit(TeacherDetailLoading());
    try {
      final detail = await _repo.fetchTeacherDetail(teacherId);
      emit(TeacherDetailLoaded(detail));
    } catch (e) {
      emit(TeacherDetailError(friendlyErrorMessage(e)));
    }
  }
}