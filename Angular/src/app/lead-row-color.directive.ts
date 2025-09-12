import { Directive, ElementRef, Input, Renderer2 } from '@angular/core';
import { ApiService } from './servizi/api.service';

@Directive({
    selector: '[appLeadRowColor]',
    standalone: false
})
export class LeadRowColorDirective {
  @Input() appLeadRowColor: any = ''; // Stato del lead

  constructor(private el: ElementRef, private renderer: Renderer2,private apiService:ApiService) {}

  ngOnInit() {
    this.setColor();
  }

  ngOnChanges() {
    this.setColor();
  }

  private setColor() {
    //console.log(this.appLeadRowColor);
    let color=this.appLeadRowColor.colore;
    this.renderer.setStyle(this.el.nativeElement, 'background-color', color);
  }
}