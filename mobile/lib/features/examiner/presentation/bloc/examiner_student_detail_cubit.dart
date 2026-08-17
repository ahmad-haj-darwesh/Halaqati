import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/examiner_models.dart';
import '../../data/examiner_repository.dart';

// --- States ---
abstract class ExaminerStudentDetailState extends Equatable {
  @override
  List<Object?> get props => [];
}

class DetailLoading extends ExaminerStudentDetailState {}

class DetailLoaded extends ExaminerStudentDetailState {
  final StudentDetail detail;
  DetailLoaded(this.detail);

  @override
  List<Object?> get props => [detail];
}

class DetailError extends ExaminerStudentDetailState {
  final String message;
  DetailError(this.message);

  @override
  List<Object?> get props => [message];
}

// --- Cubit ---
class ExaminerStudentDetailCubit extends Cubit<ExaminerStudentDetailState> {
  final ExaminerRepository _repo;

  ExaminerStudentDetailCubit(this._repo) : super(DetailLoading());

  Future<void> loadDetail(int studentId) async {
    emit(DetailLoading());
    try {
      final detail = await _repo.fetchStudentDetail(studentId);
      emit(DetailLoaded(detail));
    } catch (e) {
      emit(DetailError(e.toString()));
    }
  }
}