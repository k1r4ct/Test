/* import { Injectable } from '@angular/core';
import { v2 as dialogflow, SessionsClient } from '@google-cloud/dialogflow';

@Injectable({ providedIn: 'root' })
export class ChatService {
  private sessionClient: dialogflow.SessionsClient;
  private sessionId: string;

  constructor() {
    // Leggi le credenziali da una variabile d'ambiente
    const credentials = JSON.parse(process.env['GOOGLE_APPLICATION_CREDENTIALS'] || '{}'); 

    this.sessionClient = new dialogflow.SessionsClient({ credentials });
    this.sessionId = Math.random().toString(36).substring(7); 
  }

  async sendMessage(text: string): Promise<string> {
    try {
      const sessionPath = this.sessionClient.projectAgentSessionPath('your-project-id', this.sessionId);
      const request = {
        session: sessionPath,
        queryInput: {
          text: {
            text: text,
            languageCode: 'it-IT'
          }
        }
      };

      const responses = await this.sessionClient.detectIntent(request);
      const result = responses[0].queryResult;
      return result?.fulfillmentText   
 || '';
    } catch (error) {
      console.error('Errore durante la comunicazione con Dialogflow:', error);
      return 'Si è verificato un errore.'; // O un messaggio di errore più specifico
    }
  }
} */