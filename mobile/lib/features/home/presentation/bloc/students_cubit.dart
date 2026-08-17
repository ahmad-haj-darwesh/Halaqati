import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../data/teacher_students_repository.dart';

abstract class StudentsState extends Equatable {
  @override
  List<Object?> get props => [];
}

class StudentsLoading extends StudentsState {}

class StudentsLoaded extends StudentsState {
  final List<StudentListItemDto> allStudents;
  final List<StudentListItemDto> filteredStudents;
  StudentsLoaded(this.allStudents, this.filteredStudents);

  @override
  List<Object?> get props => [allStudents, filteredStudents];
}

class StudentsError extends StudentsState {
  final String message;
  StudentsError(this.message);

  @override
  List<Object?> get props => [message];
}

class StudentsCubit extends Cubit<StudentsState> {
  final TeacherStudentsRepository _repo;

  StudentsCubit(this._repo) : super(StudentsLoading());

  Future<void> loadStudents() async {
    emit(StudentsLoading());
    try {
      final list = await _repo.fetchAll();
      emit(StudentsLoaded(list, list));
    } catch (e) {
      emit(StudentsError(e.toString()));
    }
  }

  void search(String query) {
    if (state is StudentsLoaded) {
      final all = (state as StudentsLoaded).allStudents;
      final filtered = query.isEmpty
          ? all
          : all.where((s) => s.fullName.toLowerCase().contains(query.toLowerCase())).toList();
      emit(StudentsLoaded(all, filtered));
    }
  }
}