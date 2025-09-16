import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { LoginComponent } from './componenti/login/login.component';
import { RegistrazioneComponent } from './componenti/registrazione/registrazione.component';
import { AdminLayoutComponent } from './layouts/admin-layout/admin-layout.component';
import { ClientiComponent } from './pages/clienti/clienti.component';
import { HomeComponent } from './pages/home/home.component';

const routes: Routes = [
  { path: 'login' , component: LoginComponent},
  { path: '' , component: AdminLayoutComponent, children: [
    { path: '', component: HomeComponent},
  ]},
  { path: 'registrazione' , component: RegistrazioneComponent},
  { path: 'contatti' , component: AdminLayoutComponent, children: [
    { path: '', component: ClientiComponent},    
  ]},
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
