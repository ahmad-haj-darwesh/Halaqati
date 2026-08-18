import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:share_plus/share_plus.dart';
import '../../data/teacher_own_profile_repository.dart';
import '../../../../core/errors/api_exception.dart';

// الحالات
abstract class TeacherHomeState extends Equatable {
  @override
  List<Object?> get props => [];
}

class HomeLoading extends TeacherHomeState {}

class HomeLoaded extends TeacherHomeState {
  final Map<String, dynamic> profile;
  HomeLoaded(this.profile);

  @override
  List<Object?> get props => [profile];
}

class HomeUploadingPhoto extends TeacherHomeState {
  final Map<String, dynamic> profile;
  HomeUploadingPhoto(this.profile);
}

class HomeError extends TeacherHomeState {
  final String message;
  HomeError(this.message);

  @override
  List<Object?> get props => [message];
}

// الـ Cubit
class TeacherHomeCubit extends Cubit<TeacherHomeState> {
  final TeacherOwnProfileRepository _repo;

  TeacherHomeCubit(this._repo) : super(HomeLoading());

  Future<void> loadProfile() async {
    emit(HomeLoading());
    try {
      final data = await _repo.fetchProfile(); //
      emit(HomeLoaded(data));
    } catch (e) {
      emit(HomeError(friendlyErrorMessage(e)));
    }
  }

  Future<void> uploadPhoto(String filePath) async {
    if (state is HomeLoaded) {
      final currentProfile = (state as HomeLoaded).profile;
      emit(HomeUploadingPhoto(currentProfile));
      try {
        await _repo.updatePhoto(XFile(filePath)); //
        final updatedData = await _repo.fetchProfile(); //
        emit(HomeLoaded(updatedData));
      } catch (e) {
        emit(HomeError("فشل رفع الصورة: ${friendlyErrorMessage(e)}"));
        emit(HomeLoaded(currentProfile));
      }
    }
  }
}