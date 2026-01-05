import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { SidebarComponent } from './sidebar.component';
import { AuthService } from '../servizi/auth.service';
import { TimerComponent } from "../timer/timer.component";
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { DockModule } from 'primeng/dock';

@NgModule({
    declarations: [SidebarComponent],
    exports: [SidebarComponent],
    imports: [
        RouterModule, 
        CommonModule, 
        TimerComponent,
        MatIconModule,
        MatTooltipModule,  // Added for sidebar tooltips
        DockModule
    ]
})
export class SidebarModule {}
