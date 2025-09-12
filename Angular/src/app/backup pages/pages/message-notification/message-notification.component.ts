import { Component, OnInit, SecurityContext, ViewChild, ViewContainerRef, TemplateRef } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

@Component({
    selector: 'app-message-notification',
    templateUrl: './message-notification.component.html',
    styleUrls: ['./message-notification.component.scss'],
    standalone: false
})
export class MessageNotificationComponent implements OnInit {
  messages: any[] = [];
  counter: any;
  hoveredMessageId: number | null = null;

  constructor(private apiService: ApiService, private sanitizer: DomSanitizer) { }

  ngOnInit(): void {
    this.apiService.getMessageNotification().subscribe((messages: any) => {
      this.counter = messages.body.risposta.length;
      this.messages = messages.body.risposta.map((message: any) => {
        // Usando bypassSecurityTrustHtml per rendere l'HTML sicuro
        const trustedHtml: SafeHtml = this.sanitizer.bypassSecurityTrustHtml(message.notifica_html);
        //console.log(trustedHtml);
        return { ...message, notifica_html: trustedHtml };
      });
    });
  }

  markAsRead(message: any) {
    //console.log(this.messages);
    //console.log(this.counter);
    
    this.apiService.markReadMessage(message.id).subscribe((risposta: any) => {
      const rimuovimessaggio = this.messages.findIndex(msg => msg.id === risposta.body.risposta.id);
      if (rimuovimessaggio !== -1) {
        this.messages.splice(rimuovimessaggio, 1);
        this.counter--;
      }
      //console.log(this.messages);
      //console.log(this.counter);

      message.visualizzato = true;
    });
  }

  onHover(messageId: number) {
    this.hoveredMessageId = messageId;
  }

  onLeave() {
    this.hoveredMessageId = null;
  }
}