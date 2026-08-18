import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/examiner_models.dart';
import '../../data/examiner_repository.dart';
import '../../../../core/errors/api_exception.dart';

// --- States ---
abstract class ExaminerHomeState extends Equatable {
  @override
  List<Object?> get props => [];
}

class ExaminerHomeLoading extends ExaminerHomeState {}

class ExaminerHomeLoaded extends ExaminerHomeState {
  final ExaminerStats stats;
  final String examinerName;

  ExaminerHomeLoaded({required this.stats, required this.examinerName});

  @override
  List<Object?> get props => [stats, examinerName];
}

class ExaminerHomeError extends ExaminerHomeState {
  final String message;
  ExaminerHomeError(this.message);

  @override
  List<Object?> get props => [message];
}

// --- Cubit ---
class ExaminerHomeCubit extends Cubit<ExaminerHomeState> {
  final ExaminerRepository _repo;

  ExaminerHomeCubit(this._repo) : super(ExaminerHomeLoading());

  Future<void> loadDashboard() async {
    emit(ExaminerHomeLoading());
    try {
      // جلب البيانات بشكل متوازي (لتحسين الأداء وتقليل وقت التحميل)
      final results = await Future.wait([
        _repo.fetchMe(),
        _repo.fetchStats(),
      ]);

      final me = results[0] as Map<String, dynamic>;
      final stats = results[1] as ExaminerStats;

      emit(ExaminerHomeLoaded(
        stats: stats,
        examinerName: me['name']?.toString() ?? 'مختبر',
      ));
    } catch (e) {
      emit(ExaminerHomeError(friendlyErrorMessage(e)));
    }
  }
}