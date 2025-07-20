import 'package:go_router/go_router.dart';
import 'package:market_jango/features/auth/screens/name_screen.dart';
import 'package:market_jango/features/auth/screens/new_password_screen.dart';
import 'package:market_jango/features/auth/screens/phone_number.dart';
import 'package:market_jango/features/auth/screens/splash_screen.dart';
import 'package:market_jango/features/auth/screens/user.dart';
import 'package:market_jango/features/auth/screens/verification_screen.dart';

import '../features/auth/screens/forgot_password_screen.dart';
import '../features/auth/screens/login_screen.dart';

final GoRouter router = GoRouter(
  initialLocation: SplashScreen.routeName,
  routes: [
    GoRoute(
      path: LoginScreen.routeName,
      name: 'login',
      builder: (context, state) => const LoginScreen(),
    ),
    GoRoute(
      path: ForgotPasswordScreen.routeName,
       name: 'forgot_password',
      builder: (context, state) => const ForgotPasswordScreen(),
    ),
    GoRoute(
      path: VerificationScreen.routeName,
      name: 'verification',
      builder: (context, state) => const VerificationScreen(),
    ),
    GoRoute(
      path: NewPasswordScreen.routeName,
      name: 'new_password',
      builder: (context, state) => const NewPasswordScreen(),
    ),
    GoRoute(path:SplashScreen.routeName, 
    name: 'splash',
    builder: (context,state)=>const SplashScreen(), 
     ),
     GoRoute(path:NameScreen.routeName, 
    name: 'name',
    builder: (context,state)=>const NameScreen(), 
     ),
      GoRoute(path:UserScreen.routeName, 
    name: 'user',
    builder: (context,state)=>const UserScreen(), 
     ),
     GoRoute(path:PhoneNumberScreen.routeName, 
    name: 'phoneNumber',
    builder: (context,state)=>const PhoneNumberScreen(), 
     )
  ],
);
