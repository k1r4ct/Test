import { Injectable } from '@angular/core';

export interface DeviceInfo {
  fingerprint: string;
  device_type: string;
  device_os: string;
  device_browser: string;
  screen_resolution: string;
  cpu_cores: number | null;
  ram_gb: number | null;
  timezone_client: string;
  language: string;
  touch_support: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class DeviceFingerprintService {

  private cachedFingerprint: string | null = null;
  private cachedDeviceInfo: DeviceInfo | null = null;

  constructor() {
    this.generateFingerprint();
  }

  getDeviceInfo(): DeviceInfo {
    if (this.cachedDeviceInfo) {
      return this.cachedDeviceInfo;
    }

    this.cachedDeviceInfo = {
      fingerprint: this.getFingerprint(),
      device_type: this.getDeviceType(),
      device_os: this.getOS(),
      device_browser: this.getBrowser(),
      screen_resolution: `${screen.width}x${screen.height}`,
      cpu_cores: navigator.hardwareConcurrency || null,
      ram_gb: (navigator as any).deviceMemory || null,
      timezone_client: Intl.DateTimeFormat().resolvedOptions().timeZone,
      language: navigator.language,
      touch_support: 'ontouchstart' in window || navigator.maxTouchPoints > 0
    };

    return this.cachedDeviceInfo;
  }

  getFingerprint(): string {
    if (!this.cachedFingerprint) {
      this.generateFingerprint();
    }
    return this.cachedFingerprint || 'unknown';
  }

  private generateFingerprint(): void {
    const components: string[] = [
      `${screen.width}x${screen.height}`,
      `${screen.colorDepth}`,
      Intl.DateTimeFormat().resolvedOptions().timeZone,
      navigator.language,
      navigator.platform || 'unknown',
      String(navigator.hardwareConcurrency || 0),
      String((navigator as any).deviceMemory || 0),
      String('ontouchstart' in window),
      this.getCanvasFingerprint(),
      String(navigator.plugins?.length || 0)
    ];

    this.cachedFingerprint = this.hashString(components.join('|||'));
  }

  private getDeviceType(): string {
    const ua = navigator.userAgent.toLowerCase();
    if (/mobile|iphone|ipod|android.*mobile|windows phone/i.test(ua)) return 'Mobile';
    if (/tablet|ipad|android(?!.*mobile)/i.test(ua)) return 'Tablet';
    return 'Desktop';
  }

  private getOS(): string {
    const ua = navigator.userAgent;
    if (/Windows NT 10/.test(ua)) return 'Windows 10/11';
    if (/Mac OS X (\d+)[._](\d+)/.test(ua)) {
      const match = ua.match(/Mac OS X (\d+)[._](\d+)/);
      return match ? `macOS ${match[1]}.${match[2]}` : 'macOS';
    }
    if (/Android (\d+)/.test(ua)) {
      const match = ua.match(/Android (\d+)/);
      return match ? `Android ${match[1]}` : 'Android';
    }
    if (/iPhone|iPad/.test(ua)) {
      const match = ua.match(/OS (\d+)_(\d+)/);
      return match ? `iOS ${match[1]}.${match[2]}` : 'iOS';
    }
    if (/Linux/.test(ua)) return 'Linux';
    return 'Unknown';
  }

  private getBrowser(): string {
    const ua = navigator.userAgent;
    if (/Edg\/(\d+)/.test(ua)) return `Edge ${ua.match(/Edg\/(\d+)/)?.[1]}`;
    if (/Chrome\/(\d+)/.test(ua) && !/Chromium/.test(ua)) return `Chrome ${ua.match(/Chrome\/(\d+)/)?.[1]}`;
    if (/Firefox\/(\d+)/.test(ua)) return `Firefox ${ua.match(/Firefox\/(\d+)/)?.[1]}`;
    if (/Safari\/(\d+)/.test(ua) && !/Chrome/.test(ua)) return `Safari ${ua.match(/Version\/(\d+)/)?.[1]}`;
    return 'Unknown';
  }

  private getCanvasFingerprint(): string {
    try {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      if (!ctx) return 'no-canvas';
      ctx.textBaseline = 'top';
      ctx.font = '14px Arial';
      ctx.fillText('Semprechiaro', 2, 2);
      return this.hashString(canvas.toDataURL()).substring(0, 8);
    } catch {
      return 'error';
    }
  }

  private hashString(str: string): string {
    let hash = 5381;
    for (let i = 0; i < str.length; i++) {
      hash = ((hash << 5) + hash) + str.charCodeAt(i);
      hash = hash & hash;
    }
    return Math.abs(hash).toString(16).padStart(16, '0');
  }

  getDeviceHeaders(): { [key: string]: string } {
    const info = this.getDeviceInfo();
    return {
      'X-Device-Fingerprint': info.fingerprint,
      'X-Device-Type': info.device_type,
      'X-Device-OS': info.device_os,
      'X-Device-Browser': info.device_browser,
      'X-Screen-Resolution': info.screen_resolution,
      'X-CPU-Cores': String(info.cpu_cores || ''),
      'X-RAM-GB': String(info.ram_gb || ''),
      'X-Timezone': info.timezone_client,
      'X-Language': info.language,
      'X-Touch-Support': String(info.touch_support)
    };
  }
}