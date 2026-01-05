import {
  Component,
  OnInit,
  OnDestroy,
  Renderer2,
  ViewChild,
  ElementRef,
} from '@angular/core';
import { ROUTES } from '../../sidebar/sidebar.component';
import { Router } from '@angular/router';
import { Location } from '@angular/common';
import { AuthService } from '../../servizi/auth.service';
import { ApiService } from 'src/app/servizi/api.service';

@Component({
    moduleId: module.id,
    selector: 'app-navbar',
    templateUrl: 'navbar.component.html',
    styleUrl: './navbar.component.scss',
    standalone: false
})
export class NavbarComponent implements OnInit, OnDestroy {
  private listTitles!: any[];
  location: Location;
  private nativeElement: Node;
  private toggleButton!: Element;
  private sidebarVisible: boolean;

  public isCollapsed = true;
  @ViewChild('navbar-cmp', { static: false }) button: any;
  AuthService: any;
  Ruolo: any;
  Nome: any;

  constructor(
    location: Location,
    private renderer: Renderer2,
    private element: ElementRef,
    private router: Router,
    private authService: AuthService,
    private ApiService: ApiService
  ) {
    this.location = location;
    this.nativeElement = element.nativeElement;
    this.sidebarVisible = false;
    
    // Listener per ridimensionamento finestra
    this.handleResize = this.handleResize.bind(this);
    window.addEventListener('resize', this.handleResize);
  }

  ngOnDestroy() {
    // Pulisci listeners
    window.removeEventListener('resize', this.handleResize);
    this.removeMobileOverlay();
  }

  handleResize() {
    // Se si passa da mobile a desktop, gestisci la sidebar appropriatamente
    if (window.innerWidth >= 991) {
      // Desktop: rimuovi overlay e classes mobile
      this.removeMobileOverlay();
      const sidebar = <HTMLElement>(document.getElementsByClassName('sidebar')[0]);
      if (sidebar) {
        sidebar.classList.remove('show');
      }
      const mainPanel = <HTMLElement>(document.getElementsByClassName('main-panel')[0]);
      if (mainPanel) {
        mainPanel.style.position = '';
      }
    } else {
      // Mobile: se sidebar è aperta, gestisci appropriatamente ma senza overlay automatico
      if (this.sidebarVisible) {
        const sidebar = <HTMLElement>(document.getElementsByClassName('sidebar')[0]);
        if (sidebar) {
          sidebar.classList.add('show');
        }
        // Non aggiungiamo automaticamente l'overlay nel resize
      }
    }
  }

  ngOnInit() {
    this.ApiService.PrendiUtente().subscribe((Utente: any) => {
      this.Ruolo = Utente.user.role.descrizione;
      this.Nome = Utente.user.name;
    });
    this.listTitles = ROUTES.filter((listTitle: any) => listTitle);
    
    // Su mobile: inizializza con sidebar chiusa di default
    const savedSidebarState = localStorage.getItem('sidebarVisible');
    if (window.innerWidth < 991) {
      // Su mobile: sempre chiusa all'inizio per evitare problemi
      this.sidebarVisible = false;
    } else {
      // Su desktop: default aperta, solo se esplicitamente chiusa nel localStorage  
      this.sidebarVisible = savedSidebarState !== 'false';
    }
    
    // Pulisci eventuali overlay residui
    this.removeMobileOverlay();
    
    // Inizializzazione toggleButton con retry
    setTimeout(() => {
      const navbar: HTMLElement = this.element.nativeElement;
      this.toggleButton = navbar.getElementsByClassName('navbar-toggle')[0];
      
      // Fallback: cerca per classe diversa se non trovato
      if (!this.toggleButton) {
        const fallbackToggle = navbar.querySelector('.sidebar-toggle-wrapper') || 
                              navbar.querySelector('.modern-sidebar-toggle');
        if (fallbackToggle) {
          this.toggleButton = fallbackToggle;
        }
      }
      
      // Applica lo stato della sidebar dopo l'inizializzazione del toggle
      if (this.sidebarVisible) {
        this.applySidebarOpenState();
      } else {
        this.applySidebarCloseState();
      }
    }, 100);

    // Rimosso il listener automatico che chiudeva la sidebar
    // this.router.events.subscribe((event) => {
    //   this.sidebarClose();
    // });
  }


  getTitle() {
    var titlee = this.location.prepareExternalUrl(this.location.path());
    if (titlee.charAt(0) === '#') {
      titlee = titlee.slice(1);
    }
    for (var item = 0; item < this.listTitles.length; item++) {
      if (this.listTitles[item].path === titlee) {
        return this.listTitles[item].title;
      }
    }
    return 'Dashboard';
  }

  sidebarToggle() {
    if (this.sidebarVisible === false) {
      this.sidebarOpen();
    } else {
      this.sidebarClose();
    }
  }

  // Metodo per applicare lo stato di apertura senza animazioni
  applySidebarOpenState() {
    const toggleButton = this.toggleButton;
    const html = document.getElementsByTagName('html')[0];
    const mainPanel = <HTMLElement>(
      document.getElementsByClassName('main-panel')[0]
    );
    const sidebar = <HTMLElement>(
      document.getElementsByClassName('sidebar')[0]
    );

    if (toggleButton) {
      toggleButton.classList.add('toggled');
    }

    if (html) {
      html.classList.add('nav-open');
    }

      // Gestione specifica per mobile
  if (window.innerWidth < 991) {
    if (sidebar) {
      sidebar.classList.add('show');
    }
    // Non impostiamo overlay in questa fase iniziale
  }
  }

  // Metodo per applicare lo stato di chiusura senza animazioni
  applySidebarCloseState() {
    const html = document.getElementsByTagName('html')[0];
    const mainPanel = <HTMLElement>(
      document.getElementsByClassName('main-panel')[0]
    );
    const sidebar = <HTMLElement>(
      document.getElementsByClassName('sidebar')[0]
    );

    // Gestione specifica per mobile
    if (window.innerWidth < 991) {
      if (sidebar) {
        sidebar.classList.remove('show');
      }
      // Non gestiamo position perché non lo impostiamo più
      this.removeMobileOverlay();
    }

    if (this.toggleButton) {
      this.toggleButton.classList.remove('toggled');
    }

    if (html) {
      html.classList.remove('nav-open');
    }
  }

  sidebarOpen() {
    const toggleButton = this.toggleButton;
    const html = document.getElementsByTagName('html')[0];
    const mainPanel = <HTMLElement>(
      document.getElementsByClassName('main-panel')[0]
    );
    const sidebar = <HTMLElement>(
      document.getElementsByClassName('sidebar')[0]
    );

    // Controllo sicurezza per toggleButton
    if (toggleButton) {
      setTimeout(function () {
        toggleButton.classList.add('toggled');
      }, 500);
    } else {
      // Prova a ri-inizializzare toggleButton
      const navbar: HTMLElement = this.element.nativeElement;
      this.toggleButton = navbar.getElementsByClassName('navbar-toggle')[0];
    }

    // Controllo sicurezza per html
    if (html) {
      html.classList.add('nav-open');
    }

    // Su mobile: aggiungi classe show alla sidebar E overlay solo quando aperta manualmente
    if (window.innerWidth < 991) {
      if (sidebar) {
        sidebar.classList.add('show');
      }
      // Aggiungi overlay solo quando la sidebar viene aperta manualmente dall'utente
      this.addMobileOverlay();
    }

    this.sidebarVisible = true;
    // Salva lo stato nel localStorage
    localStorage.setItem('sidebarVisible', 'true');
  }

  sidebarClose() {
    const html = document.getElementsByTagName('html')[0];
    const mainPanel = <HTMLElement>(
      document.getElementsByClassName('main-panel')[0]
    );
    const sidebar = <HTMLElement>(
      document.getElementsByClassName('sidebar')[0]
    );

    // Su mobile: rimuovi classe show dalla sidebar
    if (window.innerWidth < 991) {
      if (sidebar) {
        sidebar.classList.remove('show');
      }
      // Rimuoviamo la gestione del position perché non lo impostiamo più
      // if (mainPanel) {
      //   setTimeout(function () {
      //     mainPanel.style.position = '';
      //   }, 500);
      // }
      // Rimuovi overlay
      this.removeMobileOverlay();
    } else {
      // Desktop: comportamento normale
      if (mainPanel) {
        setTimeout(function () {
          mainPanel.style.position = '';
        }, 500);
      }
    }

    // Controllo sicurezza per toggleButton
    if (this.toggleButton) {
      this.toggleButton.classList.remove('toggled');
    }

    this.sidebarVisible = false;
    // Salva lo stato nel localStorage
    localStorage.setItem('sidebarVisible', 'false');

    // Controllo sicurezza per html
    if (html) {
      html.classList.remove('nav-open');
    }
  }

  collapse() {
    //console.log('collapse');

    this.isCollapsed = !this.isCollapsed;
    const navbar = document.getElementsByTagName('nav')[0];
    //console.log(navbar);
    if (!this.isCollapsed) {
      navbar.classList.remove('navbar-transparent');
      navbar.classList.add('bg-white');
    } else {
      navbar.classList.add('navbar-transparent');
      navbar.classList.remove('bg-white');
    }
  }

  // Metodo per aggiungere overlay mobile
  addMobileOverlay() {
    // Rimuovi overlay esistente se presente
    this.removeMobileOverlay();
    
    const overlay = document.createElement('div');
    overlay.id = 'mobile-sidebar-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1032;
      cursor: pointer;
    `;
    
    // Chiudi sidebar quando si clicca sull'overlay
    overlay.addEventListener('click', () => {
      this.sidebarClose();
    });
    
    // Aggiungi supporto per swipe verso sinistra per chiudere sidebar
    this.addSwipeToClose(overlay);
    
    document.body.appendChild(overlay);
    
    // Previeni scroll del body
    document.body.style.overflow = 'hidden';
  }

  // Metodo per rimuovere overlay mobile
  removeMobileOverlay() {
    const existingOverlay = document.getElementById('mobile-sidebar-overlay');
    if (existingOverlay) {
      existingOverlay.remove();
    }
    
    // Ripristina scroll del body
    document.body.style.overflow = '';
  }

  // Metodo per aggiungere supporto swipe
  addSwipeToClose(element: HTMLElement) {
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;

    element.addEventListener('touchstart', (e: TouchEvent) => {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }, { passive: true });

    element.addEventListener('touchend', (e: TouchEvent) => {
      endX = e.changedTouches[0].clientX;
      endY = e.changedTouches[0].clientY;
      
      const deltaX = endX - startX;
      const deltaY = endY - startY;
      
      // Verifica se è uno swipe orizzontale verso sinistra
      if (Math.abs(deltaX) > Math.abs(deltaY) && deltaX < -50) {
        this.sidebarClose();
      }
    }, { passive: true });
  }

  sidebarLogOut() {
    //console.log('log out');
    this.authService.logOut();
  }
}
