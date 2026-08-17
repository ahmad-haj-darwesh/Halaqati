import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:intl/intl.dart';

import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class HalaqahDailyState extends Equatable {
  final DateTime date;
  
  const HalaqahDailyState({required this.date});

  @override
  List<Object?> get props => [date];
}

class HalaqahDailyLoading extends HalaqahDailyState {
  const HalaqahDailyLoading({required super.date});
}

class HalaqahDailyLoaded extends HalaqahDailyState {
  final HalaqahDaily data;

  const HalaqahDailyLoaded({required this.data, required super.date});

  @override
  List<Object?> get props => [data, date];
}

class HalaqahDailyError extends HalaqahDailyState {
  final String message;

  const HalaqahDailyError({required this.message, required super.date});

  @override
  List<Object?> get props => [message, date];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class HalaqahDailyCubit extends Cubit<HalaqahDailyState> {
  final SupervisorRepository _repo;
  final int halaqahId;

  // يبدأ الكيوبت بالتاريخ الحالي كقيمة افتراضية
  HalaqahDailyCubit({
    required SupervisorRepository repo, 
    required this.halaqahId,
  })  : _repo = repo,
        super(HalaqahDailyLoading(date: DateTime.now()));

  Future<void> loadDailyData({DateTime? newDate}) async {
    final targetDate = newDate ?? state.date;
    
    emit(HalaqahDailyLoading(date: targetDate));
    
    try {
      final dateString = DateFormat('yyyy-MM-dd').format(targetDate);
      final data = await _repo.fetchHalaqahDaily(halaqahId, date: dateString);
      
      emit(HalaqahDailyLoaded(data: data, date: targetDate));
    } catch (e) {
      emit(HalaqahDailyError(message: e.toString(), date: targetDate));
    }
  }

  void changeDate(DateTime date) {
    // منع إعادة التحميل إذا تم اختيار نفس التاريخ وكانت البيانات محملة بالفعل
    if (state is HalaqahDailyLoaded) {
      final isSameDay = state.date.year == date.year && 
                        state.date.month == date.month && 
                        state.date.day == date.day;
      if (isSameDay) return;
    }
    
    loadDailyData(newDate: date);
  }
}