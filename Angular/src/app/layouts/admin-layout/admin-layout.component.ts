import { trigger, transition, style, animate } from '@angular/animations';
import { AfterViewInit, Component, ElementRef, ViewChild } from '@angular/core';
import { Router,NavigationStart } from '@angular/router';
import { NuovocontrattoComponent } from 'src/app/pages/nuovocontratto/nuovocontratto.component';
import { LayoutScrollService } from 'src/app/servizi/layout-scroll.service';


@Component({
    selector: 'app-admin-layout',
    templateUrl: './admin-layout.component.html',
    styleUrls: ['./admin-layout.component.scss'],
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })) // Espandi e rendi visibile
            ]),
            transition(":leave", [
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })) // Riduci e rendi invisibile
            ])
        ])
    ],
    standalone: false
})
export class AdminLayoutComponent implements AfterViewInit {

  //@ViewChild('finePagina') finePagina!: ElementRef;
  targetElement: any;


  @ViewChild(NuovocontrattoComponent) NewContratto!: NuovocontrattoComponent;

  constructor(private router: Router, private srvScroll: LayoutScrollService, ) {
    this.router.events.subscribe(event => {
      if (event instanceof NavigationStart) {
        this.state = 'out';

        // Reperisci l'URL di navigazione
        const navigationUrl = event.url;
        //console.log('Navigazione verso:', navigationUrl);

        // Puoi utilizzare navigationUrl per eseguire altre azioni
        // ad esempio, per determinare se l'animazione deve essere applicata
        // o per inviare l'URL a un servizio di analisi
      }
    });
  }

  ngAfterViewInit() {
    this.srvScroll.scrollTrigger$.subscribe(() => {
      //console.log(" raccolto emitter !! ");

      //this.finePagina.scrollIntoView({ behavior: 'smooth',block: 'start' });

      this.targetElement = document.getElementById('finepagina');
      this.targetElement.scrollIntoView({
        behavior: 'smooth',
        top: 5000
      });


    });
  }


  state:any
}
