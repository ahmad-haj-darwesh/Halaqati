import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../data/teacher_daily_repository.dart';

abstract class MonthlyReportState extends Equatable {
  @override
  List<Object?> get props => [];
}

class MonthlyReportLoading extends MonthlyReportState {}

class MonthlyReportLoaded extends MonthlyReportState {
  final MonthlyReportDto report;
  final String month;
  MonthlyReportLoaded(this.report, this.month);

  @override
  List<Object?> get props => [report, month];
}

class MonthlyReportError extends MonthlyReportState {
  final String message;
  MonthlyReportError(this.message);
  @override
  List<Object?> get props => [message];
}

class MonthlyReportCubit extends Cubit<MonthlyReportState> {
  final TeacherDailyRepository _repo;

  MonthlyReportCubit(this._repo) : super(MonthlyReportLoading());

  Future<void> loadReport(String month) async {
    emit(MonthlyReportLoading());
    try {
      final report = await _repo.getMonthlyReport(month: month);
      emit(MonthlyReportLoaded(report, month));
    } catch (e) {
      emit(MonthlyReportError(e.toString()));
    }
  }
}