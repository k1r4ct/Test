import { CalendarDateFormatter, DateFormatterParams } from 'angular-calendar';
import { Injectable } from '@angular/core';
import { formatDate } from '@angular/common';

@Injectable()
export class CustomDateFormatter extends CalendarDateFormatter {

  public override weekViewHour({ date, locale }: DateFormatterParams): string {
    return formatDate(date, 'HH:mm', locale || 'it-IT'); // Fornisci un valore di default per locale
  }

  public override dayViewHour({ date, locale }: DateFormatterParams): string {
    return formatDate(date, 'HH:mm', locale || 'it-IT'); // Fornisci un valore di default per locale
  }
}