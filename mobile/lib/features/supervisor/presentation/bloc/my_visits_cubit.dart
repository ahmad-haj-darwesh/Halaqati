import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../../../core/errors/api_exception.dart';
import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class MyVisitsState extends Equatable {
  const MyVisitsState();

  @override
  List<Object?> get props => [];
}

class MyVisitsLoading extends MyVisitsState {}

class MyVisitsLoaded extends MyVisitsState {
  final List<VisitItem> items;
  final int currentPage;
  final int lastPage;
  final bool isFetchingMore;

  const MyVisitsLoaded({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    this.isFetchingMore = false,
  });

  bool get hasReachedMax => currentPage >= lastPage;

  MyVisitsLoaded copyWith({
    List<VisitItem>? items,
    int? currentPage,
    int? lastPage,
    bool? isFetchingMore,
  }) {
    return MyVisitsLoaded(
      items: items ?? this.items,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      isFetchingMore: isFetchingMore ?? this.isFetchingMore,
    );
  }

  @override
  List<Object?> get props => [items, currentPage, lastPage, isFetchingMore];
}

class MyVisitsError extends MyVisitsState {
  final String message;

  const MyVisitsError(this.message);

  @override
  List<Object?> get props => [message];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class MyVisitsCubit extends Cubit<MyVisitsState> {
  final SupervisorRepository _repo;

  MyVisitsCubit(this._repo) : super(MyVisitsLoading());

  /// تحميل الصفحة الأولى
  Future<void> loadInitial() async {
    emit(MyVisitsLoading());
    try {
      final data = await _repo.fetchMyVisits(page: 1);
      emit(MyVisitsLoaded(
        items: data.items,
        currentPage: data.currentPage,
        lastPage: data.lastPage,
      ));
    } catch (e) {
      emit(MyVisitsError(friendlyErrorMessage(e)));
    }
  }

  /// تحميل الصفحة التالية عند التمرير لأسفل
  Future<void> loadMore() async {
    if (state is! MyVisitsLoaded) return;
    final currentState = state as MyVisitsLoaded;

    // إذا وصلنا للنهاية أو كنا نحمل حالياً، نتوقف
    if (currentState.hasReachedMax || currentState.isFetchingMore) return;

    // إظهار مؤشر التحميل في أسفل القائمة
    emit(currentState.copyWith(isFetchingMore: true));

    try {
      final nextPage = currentState.currentPage + 1;
      final data = await _repo.fetchMyVisits(page: nextPage);
      
      // دمج العناصر القديمة مع الجديدة
      emit(MyVisitsLoaded(
        items: List.of(currentState.items)..addAll(data.items),
        currentPage: data.currentPage,
        lastPage: data.lastPage,
      ));
    } catch (e) {
      // في حال فشل تحميل المزيد، نكتفي بإخفاء مؤشر التحميل ونحتفظ بالبيانات الحالية
      emit(currentState.copyWith(isFetchingMore: false));
    }
  }
}