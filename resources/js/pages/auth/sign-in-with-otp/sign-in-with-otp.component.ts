import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '@/services/auth.service';

@Component({
  selector: 'app-sign-in-with-otp',
  standalone: true,
  imports: [
    CommonModule, 
    ReactiveFormsModule,
    RouterLink,
  ],
  templateUrl: './sign-in-with-otp.component.html',
  styleUrl: './sign-in-with-otp.component.css',
})
export class SignInWithOtpComponent {
  step: 'request' | 'verify' = 'request';
  emailForm: FormGroup;
  otpForm: FormGroup;
  isLoading = false;
  email = '';

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly router: Router
  ) {
    this.emailForm = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]]
    });
    
    this.otpForm = this.formBuilder.group({
      code: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  requestOtp() {
    if (this.emailForm.valid) {
      this.isLoading = true;
      this.email = this.emailForm.value.email;
      this.authService.requestOtp(this.email).subscribe({
        next: () => {
          this.isLoading = false;
          this.step = 'verify';
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Failed to send OTP:', error);
        }
      });
    } else {
      this.emailForm.markAllAsTouched();
    }
  }

  verifyOtp() {
    if (this.otpForm.valid) {
      this.isLoading = true;
      this.authService.verifyOtp({ email: this.email, code: this.otpForm.value.code }).subscribe({
        next: () => {
          this.isLoading = false;
          this.router.navigate(['/dashboard']);
        },
        error: (error) => {
          this.isLoading = false;
          console.error('OTP verification failed:', error);
        }
      });
    } else {
      this.otpForm.markAllAsTouched();
    }
  }

  resendOtp() {
    this.isLoading = true;
    this.authService.requestOtp(this.email).subscribe({
      next: () => {
        this.isLoading = false;
      },
      error: (error) => {
        this.isLoading = false;
        console.error('Failed to resend OTP:', error);
      }
    });
  }

  goBack() {
    this.step = 'request';
    this.otpForm.reset();
  }
}
