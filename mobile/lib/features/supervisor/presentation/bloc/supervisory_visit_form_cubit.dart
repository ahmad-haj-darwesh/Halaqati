import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:intl/intl.dart';

import '../../../../core/errors/api_exception.dart';
import '../../data/supervisor_models.dart';
import '../../data/supervisor_repository.dart';

// ==========================================
// الحالة (State) - دمجنا كل المتغيرات في حالة واحدة قوية
// ==========================================
class SupervisoryVisitFormState extends Equatable {
  final int step;
  final bool loadingMeta;
  final String? metaError;
  final List<Map<String, dynamic>> centers;
  final List<Map<String, dynamic>> teachers;
  
  final int? centerId;
  final int? teacherUserId;
  final DateTime visitDate;

  final int teachScore;
  final int planScore;
  final int engageScore;

  final bool submitting;
  final VisitResponse? successResponse;
  final String? submitError;

  const SupervisoryVisitFormState({
    this.step = 0,
    this.loadingMeta = true,
    this.metaError,
    this.centers = const [],
    this.teachers = const [],
    this.centerId,
    this.teacherUserId,
    required this.visitDate,
    this.teachScore = 5,
    this.planScore = 5,
    this.engageScore = 5,
    this.submitting = false,
    this.successResponse,
    this.submitError,
  });

  double get avgScore => (teachScore + planScore + engageScore) / 3.0;

  List<Map<String, dynamic>> get teachersForCenter {
    if (centerId == null) return [];
    return teachers.where((t) => _parseIntId(t['center_id']) == centerId).toList();
  }

  static int? _parseIntId(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString());
  }

  SupervisoryVisitFormState copyWith({
    int? step,
    bool? loadingMeta,
    String? metaError,
    List<Map<String, dynamic>>? centers,
    List<Map<String, dynamic>>? teachers,
    int? centerId,
    bool clearCenter = false,
    int? teacherUserId,
    bool clearTeacher = false,
    DateTime? visitDate,
    int? teachScore,
    int? planScore,
    int? engageScore,
    bool? submitting,
    VisitResponse? successResponse,
    String? submitError,
    bool clearSubmitError = false,
  }) {
    return SupervisoryVisitFormState(
      step: step ?? this.step,
      loadingMeta: loadingMeta ?? this.loadingMeta,
      metaError: metaError,
      centers: centers ?? this.centers,
      teachers: teachers ?? this.teachers,
      centerId: clearCenter ? null : (centerId ?? this.centerId),
      teacherUserId: clearTeacher ? null : (teacherUserId ?? this.teacherUserId),
      visitDate: visitDate ?? this.visitDate,
      teachScore: teachScore ?? this.teachScore,
      planScore: planScore ?? this.planScore,
      engageScore: engageScore ?? this.engageScore,
      submitting: submitting ?? this.submitting,
      successResponse: successResponse ?? this.successResponse,
      submitError: clearSubmitError ? null : (submitError ?? this.submitError),
    );
  }

  @override
  List<Object?> get props => [
        step, loadingMeta, metaError, centers, teachers, 
        centerId, teacherUserId, visitDate, 
        teachScore, planScore, engageScore, 
        submitting, successResponse, submitError,
      ];
}

// ==========================================
// الكيوبت (Cubit)
// ==========================================
class SupervisoryVisitFormCubit extends Cubit<SupervisoryVisitFormState> {
  final SupervisorRepository _repo;

  SupervisoryVisitFormCubit(this._repo) 
    : super(SupervisoryVisitFormState(visitDate: DateTime.now()));

  Future<void> loadMeta({int? presetTeacherUserId}) async {
    emit(state.copyWith(loadingMeta: true, metaError: null));
    try {
      final centers = await _repo.fetchCenters();
      final teachers = await _repo.fetchTeachers();
      
      int? cid = state.centerId;
      int? tid = presetTeacherUserId ?? state.teacherUserId;
      
      if (tid != null) {
        Map<String, dynamic>? row;
        for (final t in teachers) {
          if (SupervisoryVisitFormState._parseIntId(t['user_id']) == tid) {
            row = t;
            break;
          }
        }
        if (row != null) {
          cid = SupervisoryVisitFormState._parseIntId(row['center_id']);
          tid = SupervisoryVisitFormState._parseIntId(row['user_id']);
        }
      }

      emit(state.copyWith(
        centers: centers,
        teachers: teachers,
        centerId: cid,
        teacherUserId: tid,
        loadingMeta: false,
      ));
    } catch (e) {
      emit(state.copyWith(
        metaError: e is ApiException ? e.message : e.toString(),
        loadingMeta: false,
      ));
    }
  }

  void setStep(int step) => emit(state.copyWith(step: step));

  void selectCenter(int id) {
    emit(state.copyWith(centerId: id, clearTeacher: true));
  }

  void selectTeacher(int id) {
    emit(state.copyWith(teacherUserId: id));
  }

  void setVisitDate(DateTime date) {
    emit(state.copyWith(visitDate: date));
  }

  void updateScores({int? teach, int? plan, int? engage}) {
    emit(state.copyWith(
      teachScore: teach,
      planScore: plan,
      engageScore: engage,
    ));
  }

  Future<void> submit({String? notes, String? recommendations}) async {
    if (state.centerId == null || state.teacherUserId == null) return;
    
    emit(state.copyWith(submitting: true, clearSubmitError: true));
    try {
      final req = VisitRequest(
        teacherId: state.teacherUserId!,
        centerId: state.centerId!,
        visitDate: DateFormat('yyyy-MM-dd').format(state.visitDate),
        teachingSkillScore: state.teachScore,
        planAdherenceScore: state.planScore,
        studentEngagementScore: state.engageScore,
        notes: notes,
        recommendations: recommendations,
      );
      
      final res = await _repo.storeVisit(req);
      emit(state.copyWith(submitting: false, successResponse: res));
    } catch (e) {
      emit(state.copyWith(
        submitting: false,
        submitError: e is ApiException ? e.message : e.toString(),
      ));
    }
  }

  void resetForm() {
    emit(SupervisoryVisitFormState(
      centers: state.centers,
      teachers: state.teachers,
      visitDate: DateTime.now(),
      loadingMeta: false,
    ));
  }
}