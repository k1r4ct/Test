import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../servizi/api.service';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { AuthService } from 'src/app/servizi/auth.service';
import { Router } from '@angular/router';
@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.scss'],
    standalone: false
})
export class LoginComponent implements OnInit {

  constructor(private servzioAPI: ApiService, private authService: AuthService, private router:Router) {}
  hide=true;
  incorrectPassw=true;
  loginForm: FormGroup = new FormGroup({
    email: new FormControl('', [Validators.required, Validators.email]),
    password: new FormControl('', Validators.required)
  });

  ngOnInit(): void { } 


  gotoResetPwd(){
    this.router.navigate(['/reset-password']);
  }
  submit() {
    if (this.loginForm.valid) {
      this.authService.signIn(this.loginForm.value).subscribe(
        () => {
          // Login effettuato con successo (puoi aggiungere logica qui se necessario)
        },
        (error) => {
          this.incorrectPassw=false;
          // Gestisci gli errori di login (ad esempio, mostra un messaggio all'utente)
          console.error("Errore durante il login:", error);
        }
      );
    } else {
      // Il form non è valido, mostra un messaggio all'utente
    }
  }

  showpass() {
    this.hide = !this.hide;
  }
}