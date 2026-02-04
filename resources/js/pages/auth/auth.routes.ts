import { Routes } from '@angular/router';
import { SignInWithEmailAndPasswordComponent } from './sign-in-with-email-and-password/sign-in-with-email-and-password.component';
import { SignInWithOtpComponent } from './sign-in-with-otp/sign-in-with-otp.component';
import { SignUpWithEmailAndPasswordComponent } from './sign-up-with-email-and-password/sign-up-with-email-and-password.component';
import { SignInWithProviderComponent } from './sign-in-with-provider/sign-in-with-provider.component';
import { AuthComponent } from './auth.component';
import { ForgotPasswordComponent } from './forgot-password/forgot-password.component';
import { ResetPasswordComponent } from './reset-password/reset-password.component';

export const authRoutes: Routes = [
    {
        path: '',
        component: AuthComponent,
        children: [
            { path: 'sign-in', component: SignInWithEmailAndPasswordComponent },
            { path: 'sign-in-with-otp', component: SignInWithOtpComponent },
            { path: 'sign-up', component: SignUpWithEmailAndPasswordComponent },
            { path: 'sign-in-with-provider', component: SignInWithProviderComponent },
            { path: 'forgot-password', component: ForgotPasswordComponent },
            { path: 'reset-password', component: ResetPasswordComponent },
            { path: '', redirectTo: 'sign-in', pathMatch: 'full' }
        ]
    }
];
