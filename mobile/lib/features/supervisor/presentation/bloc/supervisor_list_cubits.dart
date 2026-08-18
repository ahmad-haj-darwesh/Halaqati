import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';

import '../../../../core/constants/api_constants.dart';
import '../../../../core/errors/api_exception.dart';
import '../../../../services/api/api_client.dart';
import '../../data/supervisor_repository.dart';

// ==========================================
// 1. Cubit قائمة المراكز (Centers)
// ==========================================
abstract class SupervisorCentersState extends Equatable {
  @override
  List<Object?> get props => [];
}

class CentersLoading extends SupervisorCentersState {}

class CentersLoaded extends SupervisorCentersState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;

  CentersLoaded(this.all, this.filtered);

  @override
  List<Object?> get props => [all, filtered];
}

class CentersError extends SupervisorCentersState {
  final String message;
  CentersError(this.message);

  @override
  List<Object?> get props => [message];
}

class SupervisorCentersCubit extends Cubit<SupervisorCentersState> {
  final SupervisorRepository _repo;
  SupervisorCentersCubit(this._repo) : super(CentersLoading());

  Future<void> load() async {
    emit(CentersLoading());
    try {
      final list = await _repo.fetchCenters();
      emit(CentersLoaded(list, list));
    } catch (e) {
      emit(CentersError(friendlyErrorMessage(e)));
    }
  }

  void search(String query) {
    if (state is CentersLoaded) {
      final currentState = state as CentersLoaded;
      final q = query.trim().toLowerCase();
      final filtered = q.isEmpty
          ? currentState.all
          : currentState.all.where((r) {
              final name = (r['name'] ?? '').toString().toLowerCase();
              final region = (r['region_name'] ?? '').toString().toLowerCase();
              return name.contains(q) || region.contains(q);
            }).toList();
      emit(CentersLoaded(currentState.all, filtered));
    }
  }
}

// ==========================================
// 2. Cubit قائمة الحلقات (Halaqahs)
// ==========================================
abstract class SupervisorHalaqahsState extends Equatable {
  @override
  List<Object?> get props => [];
}

class HalaqahsLoading extends SupervisorHalaqahsState {}

class HalaqahsLoaded extends SupervisorHalaqahsState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;
  final Map<int, int> attendanceMap;

  HalaqahsLoaded(this.all, this.filtered, this.attendanceMap);

  @override
  List<Object?> get props => [all, filtered, attendanceMap];
}

class HalaqahsError extends SupervisorHalaqahsState {
  final String message;
  HalaqahsError(this.message);

  @override
  List<Object?> get props => [message];
}

class SupervisorHalaqahsCubit extends Cubit<SupervisorHalaqahsState> {
  final SupervisorRepository _repo;
  final ApiClient _apiClient;

  SupervisorHalaqahsCubit(this._repo, this._apiClient) : super(HalaqahsLoading());

  Future<void> load() async {
    emit(HalaqahsLoading());
    try {
      // 1. جلب قائمة الحلقات
      final res = await _apiClient.get(ApiConstants.supervisorHalaqahsEndpoint);
      final data = res.data as Map<String, dynamic>;
      final list = data['data'] as List<dynamic>? ?? [];
      final hRows = list.map((e) => Map<String, dynamic>.from(e as Map)).toList();

      // 2. جلب إحصاءات الحضور
      final stats = await _repo.fetchAttendanceStats(days: 7);
      final map = <int, int>{};
      for (final h in stats.halaqahs) {
        map[h.halaqahId] = h.attendanceRate;
      }

      emit(HalaqahsLoaded(hRows, hRows, map));
    } catch (e) {
      emit(HalaqahsError(friendlyErrorMessage(e)));
    }
  }

  void search(String query) {
    if (state is HalaqahsLoaded) {
      final currentState = state as HalaqahsLoaded;
      final q = query.trim().toLowerCase();
      final filtered = q.isEmpty
          ? currentState.all
          : currentState.all.where((r) {
              final name = (r['name'] ?? '').toString().toLowerCase();
              final c = (r['center_name'] ?? '').toString().toLowerCase();
              final t = (r['teacher_name'] ?? '').toString().toLowerCase();
              return name.contains(q) || c.contains(q) || t.contains(q);
            }).toList();
      emit(HalaqahsLoaded(currentState.all, filtered, currentState.attendanceMap));
    }
  }
}

// ==========================================
// 3. Cubit قائمة المعلمين (Teachers)
// ==========================================
abstract class SupervisorTeachersState extends Equatable {
  @override
  List<Object?> get props => [];
}

class TeachersLoading extends SupervisorTeachersState {}

class TeachersLoaded extends SupervisorTeachersState {
  final List<Map<String, dynamic>> all;
  final List<Map<String, dynamic>> filtered;

  TeachersLoaded(this.all, this.filtered);

  @override
  List<Object?> get props => [all, filtered];
}

class TeachersError extends SupervisorTeachersState {
  final String message;
  TeachersError(this.message);

  @override
  List<Object?> get props => [message];
}

class SupervisorTeachersCubit extends Cubit<SupervisorTeachersState> {
  final SupervisorRepository _repo;
  SupervisorTeachersCubit(this._repo) : super(TeachersLoading());

  Future<void> load() async {
    emit(TeachersLoading());
    try {
      final list = await _repo.fetchTeachers();
      emit(TeachersLoaded(list, list));
    } catch (e) {
      emit(TeachersError(friendlyErrorMessage(e)));
    }
  }

  void search(String query) {
    if (state is TeachersLoaded) {
      final currentState = state as TeachersLoaded;
      final q = query.trim().toLowerCase();
      final filtered = q.isEmpty
          ? currentState.all
          : currentState.all.where((r) {
              final name = (r['teacher_name'] ?? '').toString().toLowerCase();
              final h = (r['halaqah_name'] ?? '').toString().toLowerCase();
              final c = (r['center_name'] ?? '').toString().toLowerCase();
              return name.contains(q) || h.contains(q) || c.contains(q);
            }).toList();
      emit(TeachersLoaded(currentState.all, filtered));
    }
  }
}