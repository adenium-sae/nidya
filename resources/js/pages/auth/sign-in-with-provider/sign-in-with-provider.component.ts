import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '@/services/auth.service';

@Component({
  selector: 'app-sign-in-with-provider',
  standalone: true,
  imports: [
    CommonModule, 
    RouterLink,
  ],
  templateUrl: './sign-in-with-provider.component.html',
  styleUrl: './sign-in-with-provider.component.css',
})
export class SignInWithProviderComponent {
  isLoading = false;
  loadingProvider = '';

  constructor(
    private readonly authService: AuthService,
    private readonly router: Router
  ) {}

  signInWithGoogle() {
    this.isLoading = true;
    this.loadingProvider = 'google';
    this.authService.signInWithProvider('google').subscribe({
      next: (response) => {
        // Usually redirect to OAuth URL
        if (response.redirect_url) {
          window.location.href = response.redirect_url;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.loadingProvider = '';
        console.error('Google sign-in failed:', error);
      }
    });
  }

  signInWithGithub() {
    this.isLoading = true;
    this.loadingProvider = 'github';
    this.authService.signInWithProvider('github').subscribe({
      next: (response) => {
        if (response.redirect_url) {
          window.location.href = response.redirect_url;
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.loadingProvider = '';
        console.error('GitHub sign-in failed:', error);
      }
    });
  }
}
