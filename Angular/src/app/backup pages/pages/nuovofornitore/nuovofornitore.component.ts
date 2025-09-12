import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from 'src/app/servizi/api.service';
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from '@angular/material/snack-bar';
import { Router } from '@angular/router';


export interface CategorySupplier{
  id:number;
  nome_categoria:string;
}

@Component({
  selector: 'app-nuovofornitore',
  templateUrl: './nuovofornitore.component.html',
  styleUrl: './nuovofornitore.component.scss',
  standalone: false,
})
export class NuovofornitoreComponent implements OnInit {
  constructor(
    private servizioApi: ApiService,
    private fb: FormBuilder,
    private _snackBar: MatSnackBar,
    private router: Router
  ) {
    this.supplierForm = this.fb.group({
      /* supplierCategory: ['', Validators.required], */
      IdCategoria: ['', Validators.required],
      supplier: ['', Validators.required],
      nomeCategoria: ['', Validators.required],
    });
  }
  value: string | undefined;
  categorysupplier: CategorySupplier[] = [];
  categorysupplierSelected: CategorySupplier[] = [];
  supplierForm: FormGroup;
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';

  ngOnInit(): void {
    this.servizioApi.recuperaCategorieFornitori().subscribe((category: any) => {
      this.categorysupplier = category.body.risposta.map((resp: any) => ({
        id: resp.id,
        nome_categoria: resp.nome_categoria,
      }));
    });
  }

  submitForm() {
    console.log(this.supplierForm.value);
    this.servizioApi
      .nuovoFornitore(this.supplierForm.value)
      .subscribe((Risposta: any) => {
        console.log(Risposta);
        this.messageSnackBar();

        // procedere con ricaricarire la rotta Gestione Prodotti
        // this.router.navigate(['/table']);
      });
  }

  messageSnackBar() {
    const snackBarRef = this._snackBar.open(
      'Nuovo Fornitore Salvato',
      'Chiudi',
      {
        horizontalPosition: this.horizontalPosition,
        verticalPosition: this.verticalPosition,
        duration: 3000, // Auto close after 5 seconds
      }
    );
    
    snackBarRef.onAction().subscribe(() => {
      // console.log('Snackbar action triggered - Close button clicked');
      // Add any additional logic to handle close button click
      // this.router.navigate(['/table'], { onSameUrlNavigation: 'reload' });
      this.router
        .navigateByUrl('/refresh', { skipLocationChange: true })
        .then(() => {
          this.router.navigateByUrl('/table'); // Torna al componente
        });
    });

    snackBarRef.afterDismissed().subscribe((info) => {
      if (!info.dismissedByAction) {
        // console.log("chiusi automaticamente");
        this.router
          .navigateByUrl('/refresh', { skipLocationChange: true })
          .then(() => {
            this.router.navigateByUrl('/table'); // Torna al componente
          });
      }
    });
  }
}
