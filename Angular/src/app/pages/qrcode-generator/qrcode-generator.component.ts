import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import * as QRCode from 'qrcode';
import { ApiService } from 'src/app/servizi/api.service';
import { environment } from "src/environments/environment";

@Component({
  selector: 'app-qrcode-generator',
  imports: [FormsModule, CommonModule],
  templateUrl: './qrcode-generator.component.html',
  styleUrl: './qrcode-generator.component.scss'
})
export class QrcodeGeneratorComponent implements OnInit {
  constructor(private apiService: ApiService) {}
  private global = { API_URL: "", apiUrl: "" };
  @ViewChild('qrCanvas', { static: false }) qrCanvas!: ElementRef<HTMLCanvasElement>;

  inputText: string = '';
  qrCodeDataURL: string = '';
  errorCorrectionLevel: 'L' | 'M' | 'Q' | 'H' = 'M';
  qrSize: number = 256;
  margin: number = 4;
  darkColor: string = '#000000';
  lightColor: string = '#FFFFFF';
  isGenerating: boolean = false;
  errorMessage: string = '';

  // Opzioni predefinite per diversi tipi di contenuto
  contentTypes = [
    { label: 'URL', value: 'url' },
    /* { label: 'Email', value: 'email' },
    { label: 'Telefono', value: 'phone' },
    { label: 'SMS', value: 'sms' },
    { label: 'WiFi', value: 'wifi' } */
  ];

  selectedContentType: string = 'text';
  
  // Campi specifici per diversi tipi di contenuto
  idUser: number = 0;
  urlInput: string = '';
  emailInput: string = '';
  phoneInput: string = '';
  smsNumber: string = '';
  smsMessage: string = '';
  wifiSSID: string = '';
  wifiPassword: string = '';
  wifiSecurity: 'WPA' | 'WEP' | 'nopass' = 'WPA';
  wifiHidden: boolean = false;

  ngOnInit(): void {
    this.global.API_URL = environment.passwdUrl;
    this.apiService.PrendiUtente().subscribe((user:any)=> {
      console.log(user);
      this.idUser = user.user.id || 0; // Imposta l'ID dell'utente se disponibile
      //console.log(this.global.API_URL);

      // Genera l'URL corretto per il form-generale con la nuova struttura della rotta
      // Utilizza il dominio corrente o localhost in sviluppo
      const baseUrl = window.location.origin || 'http://localhost:4200';
      this.urlInput = `${baseUrl}/clearportal/form-generale/${this.idUser}`;
      console.log('URL generato per QR Code:', this.urlInput);
      
      // Se c'è un utente valido, imposta automaticamente il tipo su URL
      if (this.idUser > 0) {
        this.selectedContentType = 'url';
        // Genera automaticamente il QR Code con l'URL del form
        this.generateQRCode();
      }
    });
  }

  onContentTypeChange(): void {
    this.inputText = '';
    this.errorMessage = '';
    this.qrCodeDataURL = '';
    
    // Se si seleziona URL e abbiamo un idUser valido, rigenera l'URL e il QR Code
    if (this.selectedContentType === 'url' && this.idUser > 0) {
      this.updateFormUrl();
      // Genera automaticamente il QR Code con l'URL
      setTimeout(() => this.generateQRCode(), 100);
    }
  }

  /**
   * Aggiorna l'URL del form con l'idUser corrente
   */
  private updateFormUrl(): void {
    if (this.idUser > 0) {
      const baseUrl = window.location.origin || 'http://localhost:4200';
      this.urlInput = `${baseUrl}/form-generale/${this.idUser}`;
      console.log('URL aggiornato:', this.urlInput);
    } else {
      this.urlInput = '';
      console.warn('ID utente non valido per la generazione dell\'URL');
    }
  }

  generateContentBasedText(): string {
    switch (this.selectedContentType) {
      case 'url':
        return this.urlInput;
      case 'email':
        return `mailto:${this.emailInput}`;
      case 'phone':
        return `tel:${this.phoneInput}`;
      case 'sms':
        return `sms:${this.smsNumber}?body=${encodeURIComponent(this.smsMessage)}`;
      case 'wifi':
        return `WIFI:T:${this.wifiSecurity};S:${this.wifiSSID};P:${this.wifiPassword};H:${this.wifiHidden ? 'true' : 'false'};;`;
      default:
        return this.inputText;
    }
  }

  async generateQRCode(): Promise<void> {
    const textToGenerate = this.selectedContentType === 'text' ? this.inputText : this.generateContentBasedText();
    
    if (!textToGenerate.trim()) {
      this.errorMessage = 'Inserisci del contenuto per generare il QR Code';
      return;
    }

    // Validazione speciale per URLs del form
    if (this.selectedContentType === 'url' && textToGenerate.includes('/form-generale/')) {
      const userIdMatch = textToGenerate.match(/\/form-generale\/(\d+)/);
      if (!userIdMatch || !userIdMatch[1] || userIdMatch[1] === '0') {
        this.errorMessage = 'ID utente non valido nell\'URL del form. Assicurati di essere autenticato.';
        return;
      }
    }

    this.isGenerating = true;
    this.errorMessage = '';

    try {
      const options = {
        errorCorrectionLevel: this.errorCorrectionLevel,
        width: this.qrSize,
        margin: this.margin,
        color: {
          dark: this.darkColor,
          light: this.lightColor
        }
      };

      // Genera il QR Code come Data URL
      this.qrCodeDataURL = await QRCode.toDataURL(textToGenerate, options);

      // Se il canvas è disponibile, disegna anche lì
      if (this.qrCanvas && this.qrCanvas.nativeElement) {
        await QRCode.toCanvas(this.qrCanvas.nativeElement, textToGenerate, options);
      }

    } catch (error) {
      console.error('Errore nella generazione del QR Code:', error);
      this.errorMessage = 'Errore nella generazione del QR Code. Verifica il contenuto inserito.';
    } finally {
      this.isGenerating = false;
    }
  }

  downloadQRCode(): void {
    if (!this.qrCodeDataURL) {
      this.errorMessage = 'Genera prima un QR Code';
      return;
    }

    const link = document.createElement('a');
    link.download = 'qrcode.png';
    link.href = this.qrCodeDataURL;
    link.click();
  }

  resetForm(): void {
    this.inputText = '';
    this.emailInput = '';
    this.phoneInput = '';
    this.smsNumber = '';
    this.smsMessage = '';
    this.wifiSSID = '';
    this.wifiPassword = '';
    this.selectedContentType = 'url'; // Imposta di default su URL per il form
    this.qrCodeDataURL = '';
    this.errorMessage = '';
    
    // Rigenera l'URL del form dopo il reset
    this.updateFormUrl();
    
    // Rigenera automaticamente il QR Code se abbiamo un utente valido
    if (this.idUser > 0) {
      setTimeout(() => this.generateQRCode(), 100);
    }
  }

  copyToClipboard(): void {
    if (this.qrCodeDataURL) {
      // Crea un'immagine temporanea per copiare negli appunti
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const img = new Image();
      
      img.onload = () => {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx?.drawImage(img, 0, 0);
        
        canvas.toBlob((blob) => {
          if (blob) {
            navigator.clipboard.write([
              new ClipboardItem({ 'image/png': blob })
            ]).then(() => {
              // Potresti aggiungere una notifica di successo qui
              console.log('QR Code copiato negli appunti');
            }).catch((err) => {
              console.error('Errore nella copia:', err);
            });
          }
        });
      };
      
      img.src = this.qrCodeDataURL;
    }
  }
}
