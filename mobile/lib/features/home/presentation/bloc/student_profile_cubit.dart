import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:image_picker/image_picker.dart';
import '../../data/teacher_student_profile_repository.dart';

abstract class StudentProfileState extends Equatable {
  @override
  List<Object?> get props => [];
}

class ProfileLoading extends StudentProfileState {}

class ProfileLoaded extends StudentProfileState {
  final StudentProfileScreenState data;
  final XFile? pickedPhoto;
  final bool isSaving;
  final bool isSubmitting;

  ProfileLoaded(this.data, {this.pickedPhoto, this.isSaving = false, this.isSubmitting = false});

  ProfileLoaded copyWith({StudentProfileScreenState? data, XFile? pickedPhoto, bool? isSaving, bool? isSubmitting}) {
    return ProfileLoaded(
      data ?? this.data,
      pickedPhoto: pickedPhoto ?? this.pickedPhoto,
      isSaving: isSaving ?? this.isSaving,
      isSubmitting: isSubmitting ?? this.isSubmitting,
    );
  }

  @override
  List<Object?> get props => [data, pickedPhoto, isSaving, isSubmitting];
}

class ProfileError extends StudentProfileState {
  final String message;
  ProfileError(this.message);
  @override
  List<Object?> get props => [message];
}

class StudentProfileCubit extends Cubit<StudentProfileState> {
  final TeacherStudentProfileRepository _repo;
  final int studentId;

  StudentProfileCubit(this._repo, this.studentId) : super(ProfileLoading());

  Future<void> load() async {
    emit(ProfileLoading());
    try {
      final s = await _repo.fetch(studentId);
      emit(ProfileLoaded(s));
    } catch (e) {
      emit(ProfileError(e.toString()));
    }
  }

  void pickPhoto(XFile photo) {
    if (state is ProfileLoaded) {
      emit((state as ProfileLoaded).copyWith(pickedPhoto: photo));
    }
  }

  Future<void> save({
    required String fullName, required String gender, String? birthDate,
    String? guardianName, String? guardianPhone, String? nationalId, String? notes
  }) async {
    if (state is! ProfileLoaded) return;
    final currentState = state as ProfileLoaded;
    emit(currentState.copyWith(isSaving: true));
    try {
      await _repo.updateProfile(
        studentId: studentId, fullName: fullName, gender: gender, birthDate: birthDate,
        guardianName: guardianName, guardianPhone: guardianPhone, nationalId: nationalId,
        notes: notes, newPhoto: currentState.pickedPhoto,
      );
      await load();
    } catch (e) {
      emit(currentState.copyWith(isSaving: false));
      throw e;
    }
  }

  Future<void> submit() async {
    if (state is! ProfileLoaded) return;
    final currentState = state as ProfileLoaded;
    emit(currentState.copyWith(isSubmitting: true));
    try {
      await _repo.submitForReview(studentId);
      await load();
    } catch (e) {
      emit(currentState.copyWith(isSubmitting: false));
      throw e;
    }
  }
}