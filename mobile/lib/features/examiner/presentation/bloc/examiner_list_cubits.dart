import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../data/examiner_repository.dart';
import '../../../../core/errors/api_exception.dart';

// ==========================================
// 1. Cubit قائمة الاختبارات (Tests)
// ==========================================
abstract class ExaminerTestsState extends Equatable {
  @override
  List<Object?> get props => [];
}
class TestsLoading extends ExaminerTestsState {}
class TestsLoaded extends ExaminerTestsState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;
  TestsLoaded(this.all, this.filtered);
  @override
  List<Object?> get props => [all, filtered];
}
class TestsError extends ExaminerTestsState {
  final String message;
  TestsError(this.message);
  @override
  List<Object?> get props => [message];
}

class ExaminerTestsCubit extends Cubit<ExaminerTestsState> {
  final ExaminerRepository _repo;
  ExaminerTestsCubit(this._repo) : super(TestsLoading());

  Future<void> load() async {
    emit(TestsLoading());
    try {
      final list = await _repo.fetchTests();
      emit(TestsLoaded(list, list));
    } catch (e) {
      emit(TestsError(friendlyErrorMessage(e)));
    }
  }

  void search(String query) {
    if (state is TestsLoaded) {
      final all = (state as TestsLoaded).all;
      final q = query.trim().toLowerCase();
      final filtered = q.isEmpty
          ? all
          : all.where((row) {
              final t = row['title']?.toString().toLowerCase() ?? '';
              final c = row['scope_center_name']?.toString().toLowerCase() ?? '';
              return t.contains(q) || c.contains(q);
            }).toList();
      emit(TestsLoaded(all, filtered));
    }
  }
}

// ==========================================
// 2. Cubit قائمة التعيينات (Assignments)
// ==========================================
abstract class ExaminerAssignmentsState extends Equatable {
  @override
  List<Object?> get props => [];
}
class AssignmentsLoading extends ExaminerAssignmentsState {}
class AssignmentsLoaded extends ExaminerAssignmentsState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;
  final int chipIndex;
  final String searchQuery;
  
  AssignmentsLoaded({required this.all, required this.filtered, this.chipIndex = 0, this.searchQuery = ''});
  @override
  List<Object?> get props => [all, filtered, chipIndex, searchQuery];
}
class AssignmentsError extends ExaminerAssignmentsState {
  final String message;
  AssignmentsError(this.message);
  @override
  List<Object?> get props => [message];
}

class ExaminerAssignmentsCubit extends Cubit<ExaminerAssignmentsState> {
  final ExaminerRepository _repo;
  ExaminerAssignmentsCubit(this._repo) : super(AssignmentsLoading());

  Future<void> load() async {
    emit(AssignmentsLoading());
    try {
      final list = await _repo.fetchTestAssignments();
      emit(AssignmentsLoaded(all: list, filtered: list));
    } catch (e) {
      emit(AssignmentsError(friendlyErrorMessage(e)));
    }
  }

  void filter({String? query, int? chip}) {
    if (state is AssignmentsLoaded) {
      final current = state as AssignmentsLoaded;
      final newQuery = query ?? current.searchQuery;
      final newChip = chip ?? current.chipIndex;
      
      var filteredList = current.all;
      if (newQuery.trim().isNotEmpty) {
        filteredList = filteredList.where((row) => (row['student_name']?.toString().toLowerCase() ?? '').contains(newQuery.trim().toLowerCase())).toList();
      }
      if (newChip == 1) {
        filteredList = filteredList.where((r) => r['has_result'] != true).toList();
      } else if (newChip == 2) {
        filteredList = filteredList.where((r) => r['has_result'] == true).toList();
      }
      
      emit(AssignmentsLoaded(all: current.all, filtered: filteredList, chipIndex: newChip, searchQuery: newQuery));
    }
  }
}

// ==========================================
// 3. Cubit قائمة النتائج (Results)
// ==========================================
abstract class ExaminerResultsState extends Equatable {
  @override
  List<Object?> get props => [];
}
class ResultsLoading extends ExaminerResultsState {}
class ResultsLoaded extends ExaminerResultsState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;
  ResultsLoaded(this.all, this.filtered);
  @override
  List<Object?> get props => [all, filtered];
}
class ResultsError extends ExaminerResultsState {
  final String message;
  ResultsError(this.message);
  @override
  List<Object?> get props => [message];
}

class ExaminerResultsCubit extends Cubit<ExaminerResultsState> {
  final ExaminerRepository _repo;
  ExaminerResultsCubit(this._repo) : super(ResultsLoading());

  Future<void> load() async {
    emit(ResultsLoading());
    try {
      final list = await _repo.fetchTestResults();
      emit(ResultsLoaded(list, list));
    } catch (e) {
      emit(ResultsError(friendlyErrorMessage(e)));
    }
  }

  void search(String query) {
    if (state is ResultsLoaded) {
      final all = (state as ResultsLoaded).all;
      final q = query.trim().toLowerCase();
      final filtered = q.isEmpty
          ? all
          : all.where((row) => (row['student_name']?.toString().toLowerCase() ?? '').contains(q)).toList();
      emit(ResultsLoaded(all, filtered));
    }
  }
}