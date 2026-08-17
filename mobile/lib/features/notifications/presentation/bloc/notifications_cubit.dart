import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/api_exception.dart';
import '../../../../services/api/api_client.dart';

// ==========================================
// الحالات (States)
// ==========================================
abstract class NotificationsState extends Equatable {
  const NotificationsState();

  @override
  List<Object?> get props => [];
}

class NotificationsLoading extends NotificationsState {}

class NotificationsLoaded extends NotificationsState {
  final List<Map<String, dynamic>> items;
  final int currentPage;
  final int lastPage;
  final bool isFetchingMore;

  const NotificationsLoaded({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    this.isFetchingMore = false,
  });

  bool get hasReachedMax => currentPage >= lastPage;
  bool get hasUnread => items.any((item) => item['is_read'] != true);

  NotificationsLoaded copyWith({
    List<Map<String, dynamic>>? items,
    int? currentPage,
    int? lastPage,
    bool? isFetchingMore,
  }) {
    return NotificationsLoaded(
      items: items ?? this.items,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      isFetchingMore: isFetchingMore ?? this.isFetchingMore,
    );
  }

  @override
  List<Object?> get props => [items, currentPage, lastPage, isFetchingMore];
}

class NotificationsError extends NotificationsState {
  final String message;

  const NotificationsError(this.message);

  @override
  List<Object?> get props => [message];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class NotificationsCubit extends Cubit<NotificationsState> {
  final ApiClient _apiClient;

  NotificationsCubit(this._apiClient) : super(NotificationsLoading());

  /// تحميل الصفحة الأولى من الإشعارات
  Future<void> loadInitial() async {
    emit(NotificationsLoading());
    try {
      final res = await _apiClient.get(
        ApiConstants.notificationsEndpoint,
        queryParameters: {'page': 1},
      );
      final map = res.data as Map<String, dynamic>;
      final list = (map['data'] as List<dynamic>? ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
      
      emit(NotificationsLoaded(
        items: list,
        currentPage: 1,
        lastPage: map['last_page'] as int? ?? 1,
      ));
    } catch (e) {
      emit(NotificationsError(e is ApiException ? e.message : e.toString()));
    }
  }

  /// تحميل المزيد من الإشعارات (الصفحة التالية)
  Future<void> loadMore() async {
    if (state is! NotificationsLoaded) return;
    final currentState = state as NotificationsLoaded;

    if (currentState.hasReachedMax || currentState.isFetchingMore) return;

    emit(currentState.copyWith(isFetchingMore: true));

    try {
      final nextPage = currentState.currentPage + 1;
      final res = await _apiClient.get(
        ApiConstants.notificationsEndpoint,
        queryParameters: {'page': nextPage},
      );
      
      final map = res.data as Map<String, dynamic>;
      final list = (map['data'] as List<dynamic>? ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();

      emit(NotificationsLoaded(
        items: List.of(currentState.items)..addAll(list),
        currentPage: nextPage,
        lastPage: map['last_page'] as int? ?? 1,
      ));
    } catch (e) {
      // إيقاف حالة التحميل الإضافي في حال حدوث خطأ
      emit(currentState.copyWith(isFetchingMore: false));
    }
  }

  /// تعليم إشعار محدد كمقروء وتحديث القائمة محلياً
  Future<void> markAsRead(int id) async {
    if (state is! NotificationsLoaded) return;
    final currentState = state as NotificationsLoaded;

    try {
      // إرسال الطلب للخادم
      await _apiClient.post(ApiConstants.notificationReadEndpoint(id));
      
      // تحديث الحالة محلياً لتجنب إعادة تحميل كامل القائمة
      final updatedItems = currentState.items.map((item) {
        if (item['id'] == id) {
          return {...item, 'is_read': true};
        }
        return item;
      }).toList();

      emit(currentState.copyWith(items: updatedItems));
    } catch (_) {
      // التجاهل الصامت في حال فشل تحديث القراءة
    }
  }

  /// تعليم كافة الإشعارات كمقروءة وإعادة جلب الصفحة الأولى
  Future<void> markAllAsRead() async {
    if (state is! NotificationsLoaded) return;

    try {
      await _apiClient.post(ApiConstants.notificationsReadAllEndpoint);
      await loadInitial();
    } catch (e) {
      // في حال الفشل يمكننا تمرير الخطأ، ولكن يُفضل إعادة جلب البيانات على أي حال
      final errorMessage = e is ApiException ? e.message : e.toString();
      emit(NotificationsError(errorMessage));
    }
  }
}