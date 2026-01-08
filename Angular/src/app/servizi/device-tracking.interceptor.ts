import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { DeviceFingerprintService } from './device-fingerprint.service';

@Injectable()
export class DeviceTrackingInterceptor implements HttpInterceptor {

  constructor(private deviceService: DeviceFingerprintService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const deviceHeaders = this.deviceService.getDeviceHeaders();

    const modifiedRequest = request.clone({
      setHeaders: deviceHeaders
    });

    return next.handle(modifiedRequest);
  }
}