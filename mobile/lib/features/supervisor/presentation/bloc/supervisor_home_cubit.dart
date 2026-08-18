import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';
import '../../../../core/errors/api_exception.dart';

// --- States ---
abstract class SupervisorHomeState extends Equatable {
  const SupervisorHomeState(); // إضافة const هنا اختياري، أو نزيل const من الحالات تحتها

  @override
  List<Object?> get props => [];
}

class SupervisorHomeLoading extends SupervisorHomeState {}

class SupervisorHomeLoaded extends SupervisorHomeState {
  final SupervisorStats stats;
  final String supervisorName;
  final int? managedCount;

  const SupervisorHomeLoaded({
    required this.stats,
    required this.supervisorName,
    this.managedCount,
  });

  @override
  List<Object?> get props => [stats, supervisorName, managedCount];
}

class SupervisorHomeError extends SupervisorHomeState {
  final String message;
  
  // تمت إزالة const لتتوافق مع الكلاس الأساسي أو يمكن إبقاؤها إذا أضفناها للأب
  SupervisorHomeError(this.message);

  @override
  List<Object?> get props => [message];
}

// --- Cubit ---
class SupervisorHomeCubit extends Cubit<SupervisorHomeState> {
  final SupervisorRepository _repo;

  SupervisorHomeCubit(this._repo) : super(SupervisorHomeLoading());

  Future<void> loadDashboard() async {
    emit(SupervisorHomeLoading());
    try {
      final results = await Future.wait([
        _repo.fetchMe(),
        _repo.fetchStats(),
      ]);

      final me = results[0] as Map<String, dynamic>;
      final stats = results[1] as SupervisorStats;

      emit(SupervisorHomeLoaded(
        stats: stats,
        supervisorName: me['name']?.toString() ?? 'مشرف',
        managedCount: me['managed_centers_count'] as int?,
      ));
    } catch (e) {
      emit(SupervisorHomeError(friendlyErrorMessage(e)));
    }
  }
}