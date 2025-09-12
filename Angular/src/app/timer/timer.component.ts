import { Component, OnInit } from '@angular/core';
import { AuthService } from "../servizi/auth.service";
import { ToastrService } from "ngx-toastr";



@Component({
  selector: 'app-timer',
  moduleId: module.id,
  standalone:true,
  styleUrl: './timer.component.scss',
  templateUrl: './timer.component.html',
})
export class TimerComponent implements OnInit {

  public timesession: any;
  public restante: any;
  public tempoConvertito: any;
  public intervallo: any;
  public timesesH: any;
  public timesesM: any;
  public timesesS: any;

  constructor(
    private authService: AuthService,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.timesession = localStorage.getItem("session_expired");
    this.aggiornaTimerSessione(this.timesession);
  }

  logout() {
    this.authService.logOut();
  }

  convertiSecondi(secondi: number): string {
    const ore = Math.floor(secondi / 3600);
    const minuti = Math.floor((secondi % 3600) / 60);
    const secondiResidui = secondi % 60;
    return `${ore}h ${minuti}m ${secondiResidui}s`;
  }

  aggiornaTimerSessione(tempoInSecondi: number): void {
    this.intervallo = setInterval(() => {
      tempoInSecondi--;
      
      if (tempoInSecondi === 0) {
        clearInterval(this.intervallo);
        this.logout();
      }
      this.tempoConvertito = this.convertiSecondi(tempoInSecondi);

      const regexH = /([0-9]+)h/;
      const regexM = /([0-9]+)m/;
      const regexS = /([0-9]+)s/;
      const ORE = regexH.exec(this.tempoConvertito);
      const MIN = regexM.exec(this.tempoConvertito);
      const SEC = regexS.exec(this.tempoConvertito);
      this.timesesH = parseInt(ORE![1]);
      this.timesesM = parseInt(MIN![1]);
      this.timesesS = parseInt(SEC![1]);

      this.restante = this.tempoConvertito;
      localStorage.setItem("session_expired", tempoInSecondi.toString()); // Aggiorna ogni secondo
    }, 1000);
    
    return this.restante;
  }

  refresh() {
    this.authService.refreshToken().subscribe((data: any) => {
      localStorage.setItem("jwt", data.access_token);
      localStorage.setItem("session_expired", data.expires_in);
      clearInterval(this.intervallo);
      this.ngOnInit();
      this.toastr.success(
        '<span data-notify="icon" class="nc-icon nc-bell-55"></span><span data-notify="message">Sessione aggiornata <b>restano - '+this.restante+'</b></span>',
        "",
        {
          timeOut: 4000,
          closeButton: false,
          enableHtml: true,
          toastClass: "alert alert-success alert-with-icon",
          positionClass: "toast-top-center"
        }
      );
    });
  }
}
