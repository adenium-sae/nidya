import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HttpClientModule } from '@angular/common/http';

import { ButtonModule } from 'primeng/button';
import { providePrimeNG } from 'primeng/config';
import Lara from '@primeuix/themes/lara';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AppComponent } from './app.component';

@NgModule({
    declarations: [AppComponent],
    imports: [
        BrowserModule,
        BrowserAnimationsModule,
        HttpClientModule,
        ButtonModule,
        RouterOutlet,
        CommonModule
    ],
    providers: [
        providePrimeNG({
            theme: {
                preset: Lara
            }
        })
    ],
    bootstrap: [AppComponent]
})
export class AppModule { }