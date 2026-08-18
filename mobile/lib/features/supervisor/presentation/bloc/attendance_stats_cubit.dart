import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';
import '../../../../core/errors/api_exception.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class AttendanceStatsState extends Equatable {
  final int days;
  
  const AttendanceStatsState({required this.days});

  @override
  List<Object?> get props => [days];
}

class AttendanceStatsLoading extends AttendanceStatsState {
  const AttendanceStatsLoading({required super.days});
}

class AttendanceStatsLoaded extends AttendanceStatsState {
  final AttendanceStats stats;

  const AttendanceStatsLoaded({required this.stats, required super.days});

  @override
  List<Object?> get props => [stats, days];
}

class AttendanceStatsError extends AttendanceStatsState {
  final String message;

  const AttendanceStatsError({required this.message, required super.days});

  @override
  List<Object?> get props => [message, days];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class AttendanceStatsCubit extends Cubit<AttendanceStatsState> {
  final SupervisorRepository _repo;

  // القيمة الافتراضية هي 7 أيام
  AttendanceStatsCubit(this._repo) : super(const AttendanceStatsLoading(days: 7));

  Future<void> loadStats({int? days}) async {
    final currentDays = days ?? state.days;
    
    emit(AttendanceStatsLoading(days: currentDays));
    
    try {
      final stats = await _repo.fetchAttendanceStats(days: currentDays);
      emit(AttendanceStatsLoaded(stats: stats, days: currentDays));
    } catch (e) {
      emit(AttendanceStatsError(message: friendlyErrorMessage(e), days: currentDays));
    }
  }

  void changeDays(int days) {
    if (state.days == days) return; // منع إعادة التحميل إذا تم اختيار نفس المدة
    loadStats(days: days);
  }
}