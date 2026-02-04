import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { AuthService } from '@/services/auth.service';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [
    CommonModule, 
    ReactiveFormsModule,
    RouterLink
  ],
  templateUrl: './forgot-password.component.html',
  styleUrl: './forgot-password.component.css',
})
export class ForgotPasswordComponent {
  formGroup: FormGroup;
  isLoading = false;
  isSent = false;

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService
  ) {
    this.formGroup = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]]
    });
  }

  onSubmit() {
    if (this.formGroup.valid) {
      this.isLoading = true;
      this.authService.forgotPassword(this.formGroup.value.email).subscribe({
        next: () => {
          this.isLoading = false;
          this.isSent = true;
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Forgot password failed:', error);
        }
      });
    } else {
      this.formGroup.markAllAsTouched();
    }
  }
}
