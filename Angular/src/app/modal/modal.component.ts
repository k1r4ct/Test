import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ApiService } from '../servizi/api.service';
import { FormGroup,Validators,FormBuilder } from '@angular/forms';
export interface StatiLeads{
  id:number
  stato:string
}
export interface UserForLeads{
  id:number,
  nome:string,
  cognome:string,
}
@Component({
    selector: 'app-contratto-details-dialog',
    templateUrl: './modal.component.html',
    styleUrls: ['./modal.component.scss'],
    standalone: false
})
export class ContrattoDetailsDialogComponent implements OnInit {
  form: FormGroup;
  form2: FormGroup;
  statiLead:StatiLeads[]=[];
  userForLeads:UserForLeads[]=[];
  constructor(
    public dialogRef: MatDialogRef<ContrattoDetailsDialogComponent>,
    public apiService:ApiService,
    private fb: FormBuilder,
    @Inject(MAT_DIALOG_DATA) public data: any 
    // Dati del contratto passati al dialog
  ) {
    this.form = this.fb.group({
      data_appuntamento_mat: ['', Validators.required],  // Campo per la data
      ora_appuntamento_mat: ['', Validators.required],    // Campo per l'ora
      id_lead: [''],  
      stato_id: [''],  
    });

    this.form2 = this.fb.group({  
      id_user: [''],  
      id_lead: [''],  
    });
  }
  SetOpt: FormGroup = new FormGroup({});
  showError: boolean = false;
  showCalendar=true;
  updateTrue=true;
  ruoloUser=0;
  ruoloUserText="";
  attivaRiassegnazione=true;
  ngOnInit(): void {
    console.log(this.data);
    
      if (this.data.reparto==='leads') {
        //console.log(this.data);
        this.apiService.PrendiUtente().subscribe((Ruolo:any)=>{
          //console.log(Ruolo);
          this.ruoloUser=Ruolo.user.role_id;
          this.ruoloUserText=Ruolo.user.qualification.descrizione;
          if (this.ruoloUser!=3) {
            this.attivaRiassegnazione=false;
          }
        })
        this.apiService.getStatiLeads().subscribe((risposta:any)=>{
          //console.log(risposta);
          this.statiLead = risposta.body.risposta.map((item: any) => ({
            id: item.id,
            stato: item.micro_stato
          }));
          this.form.patchValue({ id_lead: this.data.lead.id });
          this.form.patchValue({ stato_id: {id: this.data.lead.stato, microstato: this.data.lead.microstato} });
          //console.log(this.statiLead);
        })

        this.apiService.getUserForLeads().subscribe((risposta:any)=>{
          console.log(risposta);
          this.userForLeads = risposta.body.risposta.map((risposta: any) => ({
            id: risposta.id,
            nome: risposta.name,
            cognome: risposta.cognome,

          }));
        })
      }
      
  }

  onClose(): void {
    //console.log(this.data);
    
    this.dialogRef.close();
  }

  cambiaStato(stato:any,lead:any){
    //console.log(stato.value.stato);
    //console.log(stato.value.id);
    //console.log(lead.lead.id);
    if (stato.value.stato=='Appuntamento Preso') {
      this.showCalendar=false;
    }else{
      this.showCalendar=true;
    }
  }

  salvaLead() {
    // Ottieni il valore della data e dell'ora
    const dataSelezionata = this.form.get('data_appuntamento_mat')?.value;
    const oraSelezionata = this.form.get('ora_appuntamento_mat')?.value;
    const stato_id = this.form.get('stato_id')?.value;
    const id_lead=this.form.get('id_lead')?.value;
    const formData=new FormData();
    const formData2=new FormData();
    //console.log(stato_id);
    //console.log(id_lead);
    
    
    // Controlla se entrambi i valori sono validi
    if (dataSelezionata && oraSelezionata) {
      // Formattazione manuale della data
      const dataFormattata = new Date(dataSelezionata);
      

      // Estrai anno, mese, e giorno e formatta come Y-m-d
      const anno = dataFormattata.getFullYear();
      const mese = ('0' + (dataFormattata.getMonth() + 1)).slice(-2); // Mese parte da 0
      const giorno = ('0' + dataFormattata.getDate()).slice(-2);
  
      // Combina in formato Y-m-d
      const dataFinale = `${anno}-${mese}-${giorno}`;
  
      // Per l'ora, aggiungi ":00" per avere secondi nel formato H:i:s
      const oraFinale = `${oraSelezionata}:00`;
      formData.append('data_appuntamento',dataFinale);
      formData.append('ora_appuntamento',oraFinale);
      formData.append('id_lead',id_lead);
      formData.append('stato_id',stato_id.id);
      //console.log(this.form.value);
      this.apiService.appuntamentoLead(formData).subscribe((risposta:any)=>{
        //console.log(risposta);
        if (risposta.status==200) {
          window.location.reload();
        }
      })
      //console.log("ramo appuntamento");
      
      //console.log('Data formattata:', dataFinale);
      //console.log('Ora formattata:', oraFinale);
  
      // Ora puoi passare `dataFinale` e `oraFinale` alla tua logica
    } else {
      formData.append('id_lead',id_lead);
      formData.append('stato_id',stato_id.id);
      //console.log(stato_id);
      
      this.apiService.appuntamentoLead(formData).subscribe((risposta:any)=>{
        //console.log(risposta);
        if (risposta.status==200) {
          window.location.reload();
        }
      })
      //console.log("ramo default");
      
    }
  }

  cambiaAssegnazione(stato:any,lead:any){
    console.log(this.data);
    console.log(stato.stato);
    console.log(lead);
    const formData2=new FormData();

    formData2.append('id_user',stato.stato);
    formData2.append('id_lead',lead);
    
    //console.log(formData2);
    
    this.apiService.updateAssegnazioneLead(formData2).subscribe((risposta:any)=>{
      //console.log(risposta);
      
    })
    
  }
}
