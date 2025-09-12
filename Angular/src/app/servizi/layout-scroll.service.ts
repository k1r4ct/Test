import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class LayoutScrollService {
  private scrollTrigger = new Subject<void>();
  scrollTrigger$ = this.scrollTrigger.asObservable();

  triggerScroll() {
    this.scrollTrigger.next();
  }
}
