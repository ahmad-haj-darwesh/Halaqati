import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import '../../../../core/offline/offline_gateway.dart';
import '../../data/teacher_daily_repository.dart';
import '../../../../core/errors/api_exception.dart';

// --- 1. تعريف الحالات (States) ---
abstract class DailyRecordState extends Equatable {
  @override
  List<Object?> get props => [];
}

class DailyRecordLoading extends DailyRecordState {}

class DailyRecordLoaded extends DailyRecordState {
  final List<StudentTodayDto> students;
  final List<EvaluationReasonDto> reasons;
  final int updateCount; // 👈 المتغير السري لإجبار التحديث
  
  DailyRecordLoaded(this.students, this.reasons, {this.updateCount = 0});

  @override
  // 👈 أخبرنا Equatable أن يراقب هذا الرقم أيضاً
  List<Object?> get props => [students, reasons, updateCount]; 
}

class DailyRecordSaving extends DailyRecordState {
  final List<StudentTodayDto> students;
  final List<EvaluationReasonDto> reasons;
  
  DailyRecordSaving(this.students, this.reasons);
}

class DailyRecordError extends DailyRecordState {
  final String message;
  DailyRecordError(this.message);

  @override
  List<Object?> get props => [message];
}

class DailyRecordSavedSuccess extends DailyRecordState {}

/// حُفظت السجلات على الجهاز بانتظار عودة الإنترنت.
///
/// Arabic: حالة مستقلة عن [DailyRecordError] عن قصد — البيانات لم تضع، والواجهة
/// يجب أن تطمئن المعلّم لا أن تدفعه لإعادة إدخال الحلقة كاملة.
/// EN: Distinct from the error state: the data is safe, just not sent yet.
class DailyRecordQueuedOffline extends DailyRecordState {
  final String message;

  DailyRecordQueuedOffline(this.message);

  @override
  List<Object?> get props => [message];
}

// --- 2. بناء الـ Cubit (المنطق البرمجي) ---
class DailyRecordCubit extends Cubit<DailyRecordState> {
  final TeacherDailyRepository _repo;

  DailyRecordCubit(this._repo) : super(DailyRecordLoading());

  Future<void> loadData(String date) async {
    emit(DailyRecordLoading());
    try {
      final res = await _repo.getToday(date: date);
      emit(DailyRecordLoaded(res.students, res.reasons));
    } catch (e) {
      emit(DailyRecordError(friendlyErrorMessage(e)));
    }
  }

  Future<void> saveRecords(String date, List<StudentTodayDto> students, List<EvaluationReasonDto> reasons) async {
    emit(DailyRecordSaving(students, reasons));
    try {
      await _repo.upsertDailyRecords(date: date, students: students);
      emit(DailyRecordSavedSuccess());

      final res = await _repo.getToday(date: date);
      emit(DailyRecordLoaded(res.students, res.reasons));
    } on QueuedOfflineException catch (e) {
      // لا نُعيد الجلب من الخادم هنا: لا شبكة، والقيم المعروضة على الشاشة هي بالضبط
      // ما حُفظ في الطابور، فإبقاؤها كما هي أصدق من إظهار نسخة قديمة من الكاش.
      emit(DailyRecordQueuedOffline(e.message));
      emit(DailyRecordLoaded(students, reasons));
    } catch (e) {
      emit(DailyRecordError("فشل الحفظ: ${friendlyErrorMessage(e)}"));
      emit(DailyRecordLoaded(students, reasons));
    }
  }

  // 👈 تم التعديل هنا: نزيد العداد ليتم التحديث الفوري للأزرار
  void updateLocalState() {
    if (state is DailyRecordLoaded) {
      final currentState = state as DailyRecordLoaded;
      emit(DailyRecordLoaded(
        currentState.students, 
        currentState.reasons, 
        updateCount: currentState.updateCount + 1, // تغيير الرقم يجبر الواجهة على التحديث!
      ));
    }
  }
}