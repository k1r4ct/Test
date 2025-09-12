import { inject } from '@angular/core';
import { ActivatedRouteSnapshot, CanDeactivateFn, RouterStateSnapshot, UrlTree } from '@angular/router';
import { DropzoneComponent } from '../dropzone/dropzone.component';
import { Observable } from 'rxjs';

export const DropzoneDeactivateGuard: CanDeactivateFn<DropzoneComponent> = (
  component: DropzoneComponent,
  currentRoute: ActivatedRouteSnapshot,
  currentState: RouterStateSnapshot,
  nextState?: RouterStateSnapshot // Parametro opzionale
): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree => {
  if (component.dropzoneInstance) {
    //console.log("dropzone service");
    
    //console.log(component.dropzoneInstance);
    
    component.dropzoneInstance.destroy();
  }
  return true; // Permetti la navigazione
};