import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/examiner_models.dart';
import '../../data/examiner_repository.dart';

// --- States ---
abstract class ExamDailySummaryState extends Equatable {
  @override
  List<Object?> get props => [];
}

class ExamDailySummaryLoading extends ExamDailySummaryState {}

class ExamDailySummaryLoaded extends ExamDailySummaryState {
  final DailySummary summary;
  final DateTime date;

  ExamDailySummaryLoaded({required this.summary, required this.date});

  @override
  List<Object?> get props => [summary, date];
}

class ExamDailySummaryError extends ExamDailySummaryState {
  final String message;
  final DateTime date;

  ExamDailySummaryError({required this.message, required this.date});

  @override
  List<Object?> get props => [message, date];
}

// --- Cubit ---
class ExamDailySummaryCubit extends Cubit<ExamDailySummaryState> {
  final ExaminerRepository _repo;

  ExamDailySummaryCubit(this._repo) : super(ExamDailySummaryLoading());

  String _formatDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> loadSummary({DateTime? date}) async {
    final currentDate = date ??
        (state is ExamDailySummaryLoaded
            ? (state as ExamDailySummaryLoaded).date
            : (state is ExamDailySummaryError ? (state as ExamDailySummaryError).date : DateTime.now()));

    emit(ExamDailySummaryLoading());
    try {
      final summary = await _repo.fetchDailySummary(date: _formatDate(currentDate));
      emit(ExamDailySummaryLoaded(summary: summary, date: currentDate));
    } catch (e) {
      emit(ExamDailySummaryError(message: e.toString(), date: currentDate));
    }
  }

  void changeDate(DateTime newDate) {
    loadSummary(date: newDate);
  }
}