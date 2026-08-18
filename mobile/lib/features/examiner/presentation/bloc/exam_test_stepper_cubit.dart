import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/examiner_models.dart';
import '../../data/examiner_repository.dart';
import '../../../../core/errors/api_exception.dart';

// --- States ---
abstract class ExamTestStepperState extends Equatable {
  @override
  List<Object?> get props => [];
}

class StepperInitialState extends ExamTestStepperState {}

class StepperDataState extends ExamTestStepperState {
  final int step;
  final bool loadingTests;
  final String? testsError;
  final List<Map<String, dynamic>> tests;
  final Map<String, dynamic>? selectedTest;

  final bool loadingAssignments;
  final List<Map<String, dynamic>> assignments;
  final Map<String, dynamic>? selectedAssignment;
  final String assignQuery;

  final double memScore;
  final double tajScore;
  final double revScore;

  final bool submitting;
  final bool success;
  final TestResultResponse? submitResponse;

  StepperDataState({
    this.step = 0,
    this.loadingTests = true,
    this.testsError,
    this.tests = const [],
    this.selectedTest,
    this.loadingAssignments = false,
    this.assignments = const [],
    this.selectedAssignment,
    this.assignQuery = '',
    this.memScore = 70,
    this.tajScore = 70,
    this.revScore = 70,
    this.submitting = false,
    this.success = false,
    this.submitResponse,
  });

  StepperDataState copyWith({
    int? step,
    bool? loadingTests,
    String? testsError,
    List<Map<String, dynamic>>? tests,
    Map<String, dynamic>? selectedTest,
    bool? loadingAssignments,
    List<Map<String, dynamic>>? assignments,
    Map<String, dynamic>? selectedAssignment,
    String? assignQuery,
    double? memScore,
    double? tajScore,
    double? revScore,
    bool? submitting,
    bool? success,
    TestResultResponse? submitResponse,
  }) {
    return StepperDataState(
      step: step ?? this.step,
      loadingTests: loadingTests ?? this.loadingTests,
      testsError: testsError,
      tests: tests ?? this.tests,
      selectedTest: selectedTest ?? this.selectedTest,
      loadingAssignments: loadingAssignments ?? this.loadingAssignments,
      assignments: assignments ?? this.assignments,
      selectedAssignment: selectedAssignment ?? this.selectedAssignment,
      assignQuery: assignQuery ?? this.assignQuery,
      memScore: memScore ?? this.memScore,
      tajScore: tajScore ?? this.tajScore,
      revScore: revScore ?? this.revScore,
      submitting: submitting ?? this.submitting,
      success: success ?? this.success,
      submitResponse: submitResponse ?? this.submitResponse,
    );
  }

  @override
  List<Object?> get props => [
        step,
        loadingTests,
        testsError,
        tests,
        selectedTest,
        loadingAssignments,
        assignments,
        selectedAssignment,
        assignQuery,
        memScore,
        tajScore,
        revScore,
        submitting,
        success,
        submitResponse,
      ];
}

class StepperErrorNotification extends ExamTestStepperState {
  final String message;
  final StepperDataState currentState;

  StepperErrorNotification(this.message, this.currentState);

  @override
  List<Object?> get props => [message, currentState];
}

// --- Cubit ---
class ExamTestStepperCubit extends Cubit<ExamTestStepperState> {
  final ExaminerRepository _repo;

  ExamTestStepperCubit(this._repo) : super(StepperDataState());

  StepperDataState get _currentDataState {
    if (state is StepperDataState) return state as StepperDataState;
    if (state is StepperErrorNotification) return (state as StepperErrorNotification).currentState;
    return StepperDataState();
  }

  Future<void> loadTests() async {
    final cur = _currentDataState;
    emit(cur.copyWith(loadingTests: true, testsError: null));
    try {
      final list = await _repo.fetchTests();
      emit(cur.copyWith(tests: list, loadingTests: false));
    } catch (e) {
      emit(cur.copyWith(testsError: friendlyErrorMessage(e), loadingTests: false));
    }
  }

  void selectTest(Map<String, dynamic> test) {
    emit(_currentDataState.copyWith(selectedTest: test));
  }

  Future<void> goToStep2() async {
    final cur = _currentDataState;
    if (cur.selectedTest == null) return;

    emit(cur.copyWith(loadingAssignments: true));
    try {
      final testId = (cur.selectedTest!['id'] as num).toInt();
      final list = await _repo.fetchAssignmentsForTest(testId);
      emit(cur.copyWith(assignments: list, loadingAssignments: false, step: 1));
    } catch (e) {
      emit(cur.copyWith(loadingAssignments: false));
      emit(StepperErrorNotification(friendlyErrorMessage(e), _currentDataState));
    }
  }

  void selectAssignment(Map<String, dynamic> assignment) {
    emit(_currentDataState.copyWith(selectedAssignment: assignment, step: 2));
  }

  void setAssignQuery(String query) {
    emit(_currentDataState.copyWith(assignQuery: query));
  }

  void updateScores({double? mem, double? taj, double? rev}) {
    emit(_currentDataState.copyWith(
      memScore: mem ?? _currentDataState.memScore,
      tajScore: taj ?? _currentDataState.tajScore,
      revScore: rev ?? _currentDataState.revScore,
    ));
  }

  void goToStep(int step) {
    emit(_currentDataState.copyWith(step: step));
  }

  Future<void> submit({required String surah, String? notes}) async {
    final cur = _currentDataState;
    if (cur.selectedAssignment == null || cur.selectedTest == null) return;

    final req = TestResultRequest(
      studentId: (cur.selectedAssignment!['student_id'] as num).toInt(),
      testId: (cur.selectedTest!['id'] as num).toInt(),
      testedSurah: surah,
      memorizationScore: cur.memScore.round(),
      tajweedScore: cur.tajScore.round(),
      reviewScore: cur.revScore.round(),
      notes: notes,
    );

    emit(cur.copyWith(submitting: true));
    try {
      final res = await _repo.submitResult(req);
      emit(cur.copyWith(submitting: false, success: true, submitResponse: res));
    } catch (e) {
      emit(cur.copyWith(submitting: false));
      emit(StepperErrorNotification(friendlyErrorMessage(e), _currentDataState));
    }
  }

  void resetForAnother() {
    emit(StepperDataState());
    loadTests();
  }
}