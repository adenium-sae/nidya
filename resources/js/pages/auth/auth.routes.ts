import { Routes } from '@angular/router';
import { SignInWithEmailAndPasswordComponent } from './sign-in-with-email-and-password/sign-in-with-email-and-password.component';
import { SignInWithOtpComponent } from './sign-in-with-otp/sign-in-with-otp.component';
import { SignUpWithEmailAndPasswordComponent } from './sign-up-with-email-and-password/sign-up-with-email-and-password.component';
import { SignInWithProviderComponent } from './sign-in-with-provider/sign-in-with-provider.component';
import { AuthComponent } from './auth.component';

export const authRoutes: Routes = [
    {
        path: '',
        component: AuthComponent,
        children: [
            { path: 'sign-in-with-email-and-password', component: SignInWithEmailAndPasswordComponent },
            { path: 'sign-in-with-otp', component: SignInWithOtpComponent },
            { path: 'sign-up-with-email-and-password', component: SignUpWithEmailAndPasswordComponent },
            { path: 'sign-in-with-provider', component: SignInWithProviderComponent },
            { path: '', redirectTo: 'sign-in-with-email-and-password', pathMatch: 'full' }
        ]
    }
];
