import { Component,OnInit } from '@angular/core';
/* import { ChatService } from 'src/app/servizi/chat.service'; */
import { from } from 'rxjs';
interface Message {
  text: string;
  sender: 'user' | 'bot';
}
@Component({
  selector: 'app-chatbot',
  templateUrl: './chatbot.component.html',
  styleUrls: ['./chatbot.component.scss'],
  standalone:false,
})
export class ChatbotComponent implements OnInit
 {
  userInput: string = '';
  messages: Message[] = [];
  response: string = '';

  constructor(/* private dialogflowService: ChatService */) { }
  
  ngOnInit(){
    console.log("chat Bot");
    
  }
  sendMessage() {
    /* if (this.userInput.trim() === '') {
      return; // Ignora messaggi vuoti
    }

    // Aggiungi il messaggio dell'utente all'array
    this.messages.push({ text: this.userInput, sender: 'user' });
    this.userInput = ''; // Pulisci l'input

    from(this.dialogflowService.sendMessage(this.userInput)) // Usa this.userInput
    .subscribe(response => {
      // ... (resto del codice) ...
    }); */

  }
}