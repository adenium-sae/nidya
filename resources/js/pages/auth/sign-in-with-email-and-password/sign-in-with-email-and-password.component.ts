import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { AuthService, SignInPayload } from '@/services/auth.service';

@Component({
  selector: 'app-sign-in-with-email-and-password',
  standalone: true,
  imports: [
    CommonModule, 
    ReactiveFormsModule,
    RouterLink
  ],
  templateUrl: './sign-in-with-email-and-password.component.html',
  styleUrl: './sign-in-with-email-and-password.component.css',
})
export class SignInWithEmailAndPasswordComponent {
  formGroup: FormGroup;
  isLoading = false;

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly router: Router
  ) {
    this.formGroup = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required]],
      rememberMe: [false]
    });
  }

  onSubmit() {
    if (this.formGroup.valid) {
      this.isLoading = true;
      const payload: SignInPayload = {
        email: this.formGroup.value.email,
        password: this.formGroup.value.password,
        rememberMe: this.formGroup.value.rememberMe
      };
      this.authService.signInWithEmailAndPassword(payload).subscribe({
        next: (response) => {
          this.isLoading = false;
          this.router.navigate(['/dashboard']);
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Login failed:', error);
        }
      });
    } else {
        this.formGroup.markAllAsTouched();
    }
  }
}
