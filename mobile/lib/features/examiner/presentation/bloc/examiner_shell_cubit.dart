import 'package:flutter_bloc/flutter_bloc.dart';

/// Cubit بسيط جداً لإدارة رقم الشاشة الحالية (Index) في شريط التنقل السفلي
class ExaminerShellCubit extends Cubit<int> {
  ExaminerShellCubit() : super(0); // الشاشة الافتراضية هي الرئيسية (0)

  void changeTab(int index) => emit(index);
}