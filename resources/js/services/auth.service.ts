import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface SignInPayload {
  email: string;
  password: string;
  rememberMe?: boolean;
}

export interface SignUpPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface OtpPayload {
  email: string;
  code?: string;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  
  private apiUrl: string = "/api/auth";
  
  constructor(private readonly http: HttpClient) {}

  signInWithEmailAndPassword(payload: SignInPayload): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/signin`, payload);
  }

  signUpWithEmailAndPassword(payload: SignUpPayload): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/signup`, payload);
  }

  requestOtp(email: string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/otp/request`, { email });
  }

  verifyOtp(payload: OtpPayload): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/otp/verify`, payload);
  }

  signInWithProvider(provider: string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/provider/${provider}`);
  }

  forgotPassword(email: string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/forgot-password`, { email });
  }

  resetPassword(payload: { token: string; email: string; password: string; password_confirmation: string }): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/reset-password`, payload);
  }

  logout(): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/logout`, {});
  }
}
