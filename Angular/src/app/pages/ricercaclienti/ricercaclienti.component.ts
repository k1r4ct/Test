import { Component, ElementRef, OnInit, ViewChild } from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";
import { FormControl, FormGroup } from "@angular/forms";
import { ToastrService } from "ngx-toastr";
import { SharedService } from "src/app/servizi/shared.service";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { trigger, transition, style, animate } from "@angular/animations";
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from "@angular/material/snack-bar";
import { ContractServiceStatus } from "src/app/servizi/contract-status-guard.service";
import { RicercaclientiService } from "src/app/servizi/ricercaclienti.service";

@Component({
  selector: "app-ricercaclienti",
  standalone: false,
  templateUrl: "./ricercaclienti.component.html",
  styleUrl: "./ricercaclienti.component.scss",
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
        animate(
          "500ms ease-in-out",
          style({ opacity: 1, transform: "scale(1)" })
        ), // Espandi e rendi visibile
      ]),
      transition(":leave", [
        animate(
          "500ms ease-in-out",
          style({ opacity: 0, transform: "scale(0.1)" })
        ), // Riduci e rendi invisibile
      ]),
    ]),
  ],
})
export class RicercaclientiComponent implements OnInit {
  @ViewChild("ricercaRicevuta") ricercaRicevuta: ElementRef<any> =
    new ElementRef(null);
  constructor(
    private servzioAPI: ApiService,
    private toastr: ToastrService,
    private readonly SharedService: SharedService,
    private shContratto: ContrattoService,
    private _snackBar: MatSnackBar,
    private ContractService: ContractServiceStatus,
    private ricercaClienti: RicercaclientiService
  ) {}

  loginForm: FormGroup = new FormGroup({});
  inputmaxlength = 0;
  inputMinLength = 0;
  tipo = "";
  message = "";
  hidden = true;
  nuovoclienteComponent = true;
  codFPIvaval: any;
  isNomeRichiesto: boolean = true;
  state: any;
  horizontalPosition: MatSnackBarHorizontalPosition = "center";
  verticalPosition: MatSnackBarVerticalPosition = "top";
  valuePassato: any;
  valoreRicerca: any;

  ngOnInit() {
    this.SharedService.hideRicercaContratto();
    this.loginForm = new FormGroup({
      codFPIva: new FormControl(),
      tiporicerca: new FormControl(),
    });
    this.inputmaxlength = 16;
    this.inputMinLength = 16;
    //console.log(Object.keys(this.ricercaClienti.codFPartitaiva.value).length === 0);
    if (Object.keys(this.ricercaClienti.codFPartitaiva.value).length > 0) {
      this.valuePassato = this.ricercaClienti.codFPartitaiva;
      //console.log(this.valuePassato.value.codF_Piva);
      this.valoreRicerca = this.valuePassato.value.codF_Piva;

      if (this.valoreRicerca.length > 11) {
        this.codFPIvaval = this.valoreRicerca;
        this.bottontext = "CODICE FISCALE";
        this.tipotext = 1;
        this.inputmaxlength = 16;
        this.inputMinLength = 16;
      } else {
        this.codFPIvaval = this.valoreRicerca;
        this.bottontext = "PARTITA IVA";
        this.tipotext = 2;
        this.inputmaxlength = 11;
        this.inputMinLength = 11;
      }
    }
  }
  /* ngAfterViewInit(){
    if (Object.keys(this.ricercaClienti.codFPartitaiva.value).length > 0) {
      
      this.valuePassato=this.ricercaClienti.codFPartitaiva;
      //console.log(this.valuePassato.value.codF_Piva);
      this.valoreRicerca=this.valuePassato.value.codF_Piva

      if (this.valoreRicerca.length>11) {
        this.bottontext = 'CODICE FISCALE';
      this.tipotext = 1;
      this.inputmaxlength = 16;
      this.inputMinLength=16;
      }else{
        this.bottontext = 'PARTITA IVA';
      this.tipotext = 2;
      this.inputmaxlength = 11;
      this.inputMinLength=11 
      }
    }
  } */

  cerca() {
    console.log(this.loginForm.controls["codFPIva"].value);
    console.log(this.tipotext);
    this.SharedService.hideRicercaContratto();
    this.shContratto.setCodFiscalePiva(this.loginForm.value);
    const formData = new FormData();
    formData.append("codFPIva", this.loginForm.controls["codFPIva"].value);
    formData.append(
      "tiporicerca",
      this.tipotext === 1 ? "codice fiscale" : "partita iva"
    );
    this.servzioAPI
      .codFiscale_PartitaIva(this.loginForm.value)
      .subscribe((risultato: any) => {
        //console.log('---------------------------');
        console.log(risultato);

        this.SharedService.hideNewContratto();
        // Gestisci il risultato qui
        if (risultato.response == "ok") {
          let message = document.getElementById("message");
          this.hidden = false;
          this.tipo = "success";
          let status = 0;

          if (risultato.body.id != "null") {
            if (risultato.contraente.id != "null") {
              this.message = "Trovato nel database come Cliente e Contraente!";
              this.SharedService.showRicercaContratto();
              this.SharedService.hideComponent();
              this.SharedService.showNewContratto();
              status = 1;
            } else {
              this.message = "Cliente trovato!";
              this.SharedService.showRicercaContratto();
              this.SharedService.hideComponent();
              this.SharedService.showNewContratto();
              console.log("Cliente trovato!");
              console.log(this.loginForm.value);

              status = 1;
            }
          } else {
            if (risultato.contraente.id != "null") {
              this.message = "Trovato nel database Solo Contraente!";
              this.tipo = "warning";
              console.log("Trovato nel database Solo Contraente!");
              console.log(this.loginForm.value);
              this.shContratto.setCodFiscalePiva(this.loginForm.value);
              this.SharedService.showRicercaContratto();
              this.SharedService.setTipoCliente(this.bottontext);
              this.SharedService.showComponent();
              if (this.tipotext == 1) {
                this.SharedService.setCodiceFiscale(
                  this.loginForm.controls["codFPIva"].value
                );
              } else {
                this.SharedService.setPartitaIva(
                  this.loginForm.controls["codFPIva"].value
                );
              }
              status = 2;
            }
          }

          this.shContratto.setCodFiscalePiva(this.loginForm.value);
          this.shContratto.setStatoContratto(true);
          this.shContratto.setIdUtente(
            Number(localStorage.getItem("userLogin"))
          );
          this.shContratto.setIdCliente(Number(risultato.body.id));
          this.shContratto.setTipoCliente(risultato.body.tipo);
        } else {
          this.hidden = false;
          this.tipo = "danger";
          this.message = "Cliente non trovato";
          this.SharedService.setTipoCliente(this.bottontext);

          if (this.tipotext == 1) {
            this.SharedService.setCodiceFiscale(
              this.loginForm.controls["codFPIva"].value
            );
          } else {
            this.SharedService.setPartitaIva(
              this.loginForm.controls["codFPIva"].value
            );
          }

          this.SharedService.showComponent();
          this.SharedService.hideNewContratto();
          this.shContratto.setIdCliente(null);
          this.shContratto.setTipoCliente(null);
          //this.shContratto.setProdotto(null,null);

          //console.log(this.hidden);
        }
      });

    //this.servzioAPI.codFiscale_PartitaIva(codFPIva)
  }

  bottontext = "CODICE FISCALE";
  tipotext = 1;

  changeText() {
    console.log(this.loginForm.value);

    this.codFPIvaval = "";
    if (this.tipotext == 1) {
      this.bottontext = "PARTITA IVA";
      this.tipotext = 2;
      this.inputmaxlength = 11;
      this.inputMinLength = 11; // controllo solo 12 caratteri per Partita Iva
    } else {
      this.bottontext = "CODICE FISCALE";
      this.tipotext = 1;
      this.inputmaxlength = 16;
      this.inputMinLength = 16; // controllo solo 16 caratteri per Codice Fiscale
    }
  }
}
