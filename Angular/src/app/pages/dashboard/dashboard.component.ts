import { Component, OnInit, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { trigger, style, animate, transition } from '@angular/animations';
import { Subscription } from 'rxjs';

@Component({
    selector: 'dashboard-cmp',
    moduleId: module.id,
    templateUrl: 'dashboard.component.html',
    styleUrls: ['dashboard.component.scss'],
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.95)" }),
                animate("400ms ease-out", style({ opacity: 1, transform: "scale(1)" }))
            ]),
            transition(":leave", [
                animate("300ms ease-in", style({ opacity: 0, transform: "scale(0.95)" }))
            ])
        ])
    ],
    standalone: false
})
export class DashboardComponent implements OnInit, OnDestroy {

  User: any;
  isLoading = true;
  private sub!: Subscription;

  constructor(private servizioAPI: ApiService) {}

  ngOnInit() {
    this.sub = this.servizioAPI.PrendiUtente().subscribe(user => {
      this.User = user.user;
      // Small delay to let user-cmp initialize before revealing
      setTimeout(() => {
        this.isLoading = false;
      }, 100);
    });
  }

  ngOnDestroy() {
    if (this.sub) {
      this.sub.unsubscribe();
    }
  }
}