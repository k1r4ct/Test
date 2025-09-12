import { Injector, inject } from "@angular/core";
import { ActivatedRouteSnapshot, CanActivateFn, Router, RouterStateSnapshot } from "@angular/router";
import { AuthService } from "./auth.service";

export const activateUsersFn: CanActivateFn = function (route: ActivatedRouteSnapshot, state: RouterStateSnapshot){
  //console.log(route,state);
  return inject(AuthService).isUserLogin()
  ? true
  : inject(Router).createUrlTree(['/login']);
}

