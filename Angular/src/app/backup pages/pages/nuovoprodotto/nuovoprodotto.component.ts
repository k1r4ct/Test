import { Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from '@angular/material/snack-bar';

export interface ProdottiNew{
  macro_product:any[];
  supplier:any[];
  supplier_category:any[];

}
export interface MacroProduct{
  id:any[];
  descrizione:any[];

}
@Component({
  selector: 'app-nuovoprodotto',
  templateUrl: './nuovoprodotto.component.html',
  styleUrl: './nuovoprodotto.component.scss',
  standalone: false,
})
export class NuovoprodottoComponent implements OnInit {
  prodottiNew: ProdottiNew = {
    macro_product: [],
    supplier: [],
    supplier_category: [],
  };
  macroProduct: MacroProduct[] = [];
  productForm: FormGroup;
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';

  showError: boolean = false;
  constructor(
    private servizioApi: ApiService,
    private fb: FormBuilder,
    private _snackBar: MatSnackBar
  ) {
    this.productForm = this.fb.group({
      /* supplierCategory: ['', Validators.required], */
      supplier: ['', Validators.required],
      macroProduct: ['', Validators.required],
      descrizione: ['', Validators.required],
      /* supplier_id: ['', Validators.required], */ // Questo sarà impostato automaticamente in base alla selezione del fornitore
      attivo: ['', Validators.required],
      /* macro_product_id: ['', Validators.required], */ // Questo sarà impostato automaticamente in base alla selezione del macro prodotto
      gettone: ['', Validators.required],
      inizioOfferta: ['', Validators.required],
      fineOfferta: ['', Validators.required],
    });
  }

  ngOnInit(): void {
    this.servizioApi.nuovoProdotto().subscribe((Oggetti: any) => {
      //console.log(Oggetti.body.risposta);
      this.prodottiNew = {
        macro_product: Oggetti.body.risposta.macro_product.filter(
          (item: any) => Object.keys(item).length > 0
        ),
        supplier: Oggetti.body.risposta.supplier.filter(
          (item: any) => Object.keys(item).length > 0
        ),
        supplier_category: Oggetti.body.risposta.supplier_category.filter(
          (item: any) => Object.keys(item).length > 0
        ),
      };

      //console.log(Oggetti);
      //console.log(this.prodottiNew);
    });
  }

  messageSnackBarMacro() {
    const snackBarRef = this._snackBar.open(
      'Selezionare Prima il Fornitore',
      'Chiudi',
      {
        horizontalPosition: this.horizontalPosition,
        verticalPosition: this.verticalPosition,
        duration: 4000, // Auto close after 5 seconds
      }
    );
  }

  controllamacro() {
    // console.log('controllamacro');

    if(this.macroProduct.length == 0){
      this.messageSnackBarMacro();
    }

  }

  fornitore(supplierId: number) {
    this.macroProduct = [];
    this.servizioApi.getMacroProduct(supplierId).subscribe((Risposta: any) => {
      //console.log(Risposta);

      // Controlla se la risposta contiene un array o un singolo oggetto
      if (Array.isArray(Risposta.body.risposta)) {
        this.macroProduct = Risposta.body.risposta.map((Macro: any) => ({
          id: Macro.id,
          descrizione: Macro.descrizione,
        }));
      } else if (Risposta.body.risposta) {
        // Se è un singolo oggetto, crea un array con quell'oggetto
        this.macroProduct = [
          {
            id: Risposta.body.risposta.id,
            descrizione: Risposta.body.risposta.descrizione,
          },
        ];
      } else {
        this.macroProduct = []; // Se non ci sono dati, imposta un array vuoto
      }

      //console.log(this.macroProduct);
    });
  }

  submitForm() {
    //console.log(this.productForm.value);
    this.servizioApi
      .storeNewProduct(this.productForm.value)
      .subscribe((Risposta: any) => {
        //console.log(Risposta);
      });
    if (this.productForm.valid) {
      // Invia i dati del form all'API per creare il nuovo prodotto
    }
  }
}
