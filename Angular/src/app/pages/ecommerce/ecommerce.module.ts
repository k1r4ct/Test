import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// Angular Material
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatBadgeModule } from '@angular/material/badge';
import { MatDialogModule } from '@angular/material/dialog';
import { MatSnackBarModule } from '@angular/material/snack-bar';

// Components
import { StoreCatalogComponent } from './store-catalog/store-catalog.component';
import { CartComponent } from './cart/cart.component';
import { CheckoutComponent } from './checkout/checkout.component';
import { OrderHistoryComponent } from './order-history/order-history.component';
import { OrderDetailComponent } from './order-detail/order-detail.component';

// Routes
const routes: Routes = [
  {
    path: '',
    redirectTo: 'catalog',
    pathMatch: 'full'
  },
  {
    path: 'catalog',
    component: StoreCatalogComponent,
    data: { title: 'Catalogo Premi' }
  },
  {
    path: 'cart',
    component: CartComponent,
    data: { title: 'Carrello' }
  },
  {
    path: 'checkout',
    component: CheckoutComponent,
    data: { title: 'Checkout' }
  },
  {
    path: 'orders',
    component: OrderHistoryComponent,
    data: { title: 'I miei Ordini' }
  },
  {
    path: 'orders/:id',
    component: OrderDetailComponent,
    data: { title: 'Dettaglio Ordine' }
  }
];

@NgModule({
  declarations: [
    StoreCatalogComponent,
    CartComponent,
    CheckoutComponent,
    OrderHistoryComponent,
    OrderDetailComponent
  ],
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
    // Material
    MatIconModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCheckboxModule,
    MatProgressSpinnerModule,
    MatTooltipModule,
    MatCardModule,
    MatChipsModule,
    MatBadgeModule,
    MatDialogModule,
    MatSnackBarModule
  ],
  exports: [
    RouterModule
  ]
})
export class EcommerceModule { }
