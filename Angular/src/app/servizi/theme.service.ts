import { Injectable, Inject, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ThemeService {
  private readonly THEME_KEY = 'semprechiaro-theme';
  private readonly DARK_CLASS = 'dark-mode';
  
  private isDarkMode = new BehaviorSubject<boolean>(false);
  public isDarkMode$ = this.isDarkMode.asObservable();

  constructor(@Inject(PLATFORM_ID) private platformId: Object) {
    if (isPlatformBrowser(this.platformId)) {
      this.loadTheme();
    }
  }

  /**
   * Load saved theme preference from localStorage
   */
  private loadTheme(): void {
    const savedTheme = localStorage.getItem(this.THEME_KEY);
    
    if (savedTheme === 'dark') {
      this.enableDarkMode();
    } else if (savedTheme === 'light') {
      this.disableDarkMode();
    } else {
      // Check system preference if no saved preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (prefersDark) {
        this.enableDarkMode();
      }
    }
  }

  /**
   * Toggle between dark and light mode
   */
  toggleTheme(): void {
    if (this.isDarkMode.value) {
      this.disableDarkMode();
    } else {
      this.enableDarkMode();
    }
  }

  /**
   * Enable dark mode
   */
  enableDarkMode(): void {
    document.documentElement.classList.add(this.DARK_CLASS);
    localStorage.setItem(this.THEME_KEY, 'dark');
    this.isDarkMode.next(true);
  }

  /**
   * Disable dark mode (light mode)
   */
  disableDarkMode(): void {
    document.documentElement.classList.remove(this.DARK_CLASS);
    localStorage.setItem(this.THEME_KEY, 'light');
    this.isDarkMode.next(false);
  }

  /**
   * Get current theme state
   */
  getIsDarkMode(): boolean {
    return this.isDarkMode.value;
  }
}
